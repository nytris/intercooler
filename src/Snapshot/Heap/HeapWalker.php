<?php

/*
 * Nytris Intercooler - PHP heap snapshotting for accelerated bootstrapping.
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/intercooler/
 *
 * Released under the MIT license.
 * https://github.com/nytris/intercooler/raw/main/MIT-LICENSE.txt
 */

declare(strict_types=1);

namespace Nytris\Intercooler\Snapshot\Heap;

use ReflectionClass;
use ReflectionProperty;

/**
 * Class HeapWalker.
 *
 * Iterates PHP GC roots to discover the values that make up the current PHP heap.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class HeapWalker implements HeapWalkerInterface
{
    /**
     * Superglobals and the GLOBALS self-reference are never included in snapshots.
     *
     * @var string[]
     */
    private const EXCLUDED_GLOBALS = [
        'GLOBALS',
        '_GET',
        '_POST',
        '_COOKIE',
        '_FILES',
        '_SERVER',
        '_ENV',
        '_REQUEST',
        '_SESSION',
        'HTTP_RAW_POST_DATA',
    ];

    /**
     * @param string[] $excludedClasses FQCNs of classes to exclude from static property snapshotting.
     * @param bool $includeGlobals Whether to include global variables in the snapshot.
     */
    public function __construct(
        private readonly array $excludedClasses = [],
        private readonly bool $includeGlobals = true
    ) {
    }

    /**
     * @inheritDoc
     */
    public function walkClassStaticProperties(): iterable
    {
        foreach (get_declared_classes() as $className) {
            if (in_array($className, $this->excludedClasses, true)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            // Skip internal PHP classes; their static state is part of the C runtime.
            if ($reflection->isInternal()) {
                continue;
            }

            $properties = [];

            foreach ($reflection->getProperties(ReflectionProperty::IS_STATIC) as $prop) {
                $declaringClass = $prop->getDeclaringClass();

                // Include the property only for the class that "owns" its storage slot:
                // - If declared by a trait: each host class has its own slot, so include it.
                // - If declared by a regular class: only include it for that declaring class.
                if (!$declaringClass->isTrait() && $declaringClass->getName() !== $className) {
                    continue;
                }

                $properties[] = $prop;
            }

            if (!empty($properties)) {
                yield $className => $properties;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function walkGlobals(): iterable
    {
        if (!$this->includeGlobals) {
            return;
        }

        foreach (array_keys($GLOBALS) as $name) {
            if (in_array($name, self::EXCLUDED_GLOBALS, true)) {
                continue;
            }

            yield $name => $GLOBALS[$name];
        }
    }
}
