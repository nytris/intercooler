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

use ReflectionProperty;

/**
 * Interface HeapWalkerInterface.
 *
 * Iterates PHP GC roots to discover the values that make up the current PHP heap.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface HeapWalkerInterface
{
    /**
     * Yields class names and their accessible static properties.
     *
     * Internal PHP classes are excluded. Properties inherited from a non-trait parent class
     * are excluded (they will appear under the declaring class). Properties from traits used by
     * the class are included (each host class has its own storage slot for trait static properties).
     *
     * @return iterable<class-string, ReflectionProperty[]>
     */
    public function walkClassStaticProperties(): iterable;

    /**
     * Yields global variable names and their values.
     *
     * Superglobals and the `GLOBALS` self-reference are excluded.
     *
     * @return iterable<string, mixed>
     */
    public function walkGlobals(): iterable;
}
