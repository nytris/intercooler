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

namespace Nytris\Intercooler\Implementation;

use Nytris\Intercooler\Snapshot\Dump\ValueDumperInterface;
use Nytris\Intercooler\Snapshot\Heap\HeapWalkerInterface;
use Nytris\Intercooler\Snapshot\SnapshotterInterface;
use Nytris\Intercooler\Snapshot\Write\SnapshotWriterInterface;

/**
 * Interface ImplementationInterface.
 *
 * Provides the wired-up service objects that make up an Intercooler installation.
 * Developers may replace individual services by subclassing Implementation and
 * overriding the relevant getters, or provide an entirely custom implementation.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface ImplementationInterface
{
    /**
     * Fetches the heap walker service.
     */
    public function getHeapWalker(): HeapWalkerInterface;

    /**
     * Fetches the snapshot writer service.
     */
    public function getSnapshotWriter(): SnapshotWriterInterface;

    /**
     * Fetches the snapshotter service.
     */
    public function getSnapshotter(): SnapshotterInterface;

    /**
     * Fetches the value dumper service.
     */
    public function getValueDumper(): ValueDumperInterface;
}
