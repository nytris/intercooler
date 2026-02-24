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

use Nytris\Intercooler\IntercoolerPackageInterface;
use Nytris\Intercooler\Snapshot\Dump\ValueDumper;
use Nytris\Intercooler\Snapshot\Dump\ValueDumperInterface;
use Nytris\Intercooler\Snapshot\Heap\HeapWalker;
use Nytris\Intercooler\Snapshot\Heap\HeapWalkerInterface;
use Nytris\Intercooler\Snapshot\Snapshotter;
use Nytris\Intercooler\Snapshot\SnapshotterInterface;
use Nytris\Intercooler\Snapshot\Write\SnapshotWriter;
use Nytris\Intercooler\Snapshot\Write\SnapshotWriterInterface;
use Nytris\Intercooler\Type\Handler\FileResourceHandler;

/**
 * Class Implementation.
 *
 * Default wiring of the Intercooler service objects.
 * Developers may subclass this and override individual service getters to customise behaviour,
 * or provide an entirely custom ImplementationInterface via IntercoolerPackage.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Implementation implements ImplementationInterface
{
    private ?HeapWalkerInterface $heapWalker = null;
    private ?SnapshotterInterface $snapshotter = null;
    private ?SnapshotWriterInterface $snapshotWriter = null;
    private ?ValueDumperInterface $valueDumper = null;

    public function __construct(
        private readonly IntercoolerPackageInterface $package
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getHeapWalker(): HeapWalkerInterface
    {
        return $this->heapWalker ??= new HeapWalker(
            $this->package->getExcludedClasses(),
            $this->package->shouldIncludeGlobals()
        );
    }

    /**
     * @inheritDoc
     */
    public function getSnapshotWriter(): SnapshotWriterInterface
    {
        return $this->snapshotWriter ??= new SnapshotWriter();
    }

    /**
     * @inheritDoc
     */
    public function getSnapshotter(): SnapshotterInterface
    {
        return $this->snapshotter ??= new Snapshotter(
            $this->getHeapWalker(),
            $this->getValueDumper(),
            $this->getSnapshotWriter()
        );
    }

    /**
     * @inheritDoc
     */
    public function getValueDumper(): ValueDumperInterface
    {
        return $this->valueDumper ??= new ValueDumper([
            ...$this->package->getAdditionalTypeHandlers(),
            new FileResourceHandler(),
        ]);
    }
}
