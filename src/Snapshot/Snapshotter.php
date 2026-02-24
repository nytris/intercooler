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

namespace Nytris\Intercooler\Snapshot;

use Nytris\Intercooler\Snapshot\Dump\DumpContext;
use Nytris\Intercooler\Snapshot\Dump\DumpContextInterface;
use Nytris\Intercooler\Snapshot\Dump\ValueDumperInterface;
use Nytris\Intercooler\Snapshot\Heap\HeapWalkerInterface;
use Nytris\Intercooler\Snapshot\Statement\StatementBuffer;
use Nytris\Intercooler\Snapshot\Statement\StatementBufferInterface;
use Nytris\Intercooler\Snapshot\Write\SnapshotWriterInterface;

/**
 * Class Snapshotter.
 *
 * Orchestrates the snapshotting of the current PHP heap to a PHP file.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Snapshotter implements SnapshotterInterface
{
    public function __construct(
        private readonly HeapWalkerInterface $heapWalker,
        private readonly ValueDumperInterface $valueDumper,
        private readonly SnapshotWriterInterface $snapshotWriter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function takeSnapshot(
        string $snapshotPath,
        DumpContextInterface $context = new DumpContext(),
        StatementBufferInterface $buffer = new StatementBuffer()
    ): void {
        // Walk all static properties of all user-defined classes.
        foreach ($this->heapWalker->walkClassStaticProperties() as $className => $properties) {
            foreach ($properties as $prop) {
                if (!$prop->isInitialized()) {
                    continue;
                }

                $propName = $prop->getName();
                $value = $prop->getValue();
                $dumpedValue = $this->valueDumper->dump($value, $context, $buffer);

                $buffer->addStaticPropertySetup(
                    sprintf(
                        '(function ($v): void { $p = (new \\ReflectionClass(\\%s::class))->getProperty(%s); $p->setValue(null, $v); })(%s);',
                        $className,
                        var_export($propName, true),
                        $dumpedValue
                    )
                );
            }
        }

        // Walk all global variables.
        foreach ($this->heapWalker->walkGlobals() as $name => $value) {
            $dumpedValue = $this->valueDumper->dump($value, $context, $buffer);

            $buffer->addGlobal(
                sprintf('$GLOBALS[%s] = %s;', var_export($name, true), $dumpedValue)
            );
        }

        $this->snapshotWriter->write($snapshotPath, $buffer);
    }
}
