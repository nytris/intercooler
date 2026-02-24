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

namespace Nytris\Intercooler\Tests\Unit\Implementation;

use Mockery\MockInterface;
use Nytris\Intercooler\Implementation\Implementation;
use Nytris\Intercooler\IntercoolerPackageInterface;
use Nytris\Intercooler\Snapshot\Dump\ValueDumperInterface;
use Nytris\Intercooler\Snapshot\Heap\HeapWalkerInterface;
use Nytris\Intercooler\Snapshot\SnapshotterInterface;
use Nytris\Intercooler\Snapshot\Write\SnapshotWriterInterface;
use Nytris\Intercooler\Tests\AbstractTestCase;

/**
 * Class ImplementationTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ImplementationTest extends AbstractTestCase
{
    private Implementation $implementation;
    private MockInterface&IntercoolerPackageInterface $package;

    public function setUp(): void
    {
        $this->package = mock(IntercoolerPackageInterface::class, [
            'getAdditionalTypeHandlers' => [],
            'getExcludedClasses' => [],
            'shouldIncludeGlobals' => true,
        ]);

        $this->implementation = new Implementation($this->package);
    }

    public function testGetHeapWalkerReturnsHeapWalkerInterface(): void
    {
        static::assertInstanceOf(HeapWalkerInterface::class, $this->implementation->getHeapWalker());
    }

    public function testGetHeapWalkerReturnsSameInstanceOnSubsequentCalls(): void
    {
        static::assertSame(
            $this->implementation->getHeapWalker(),
            $this->implementation->getHeapWalker()
        );
    }

    public function testGetValueDumperReturnsValueDumperInterface(): void
    {
        static::assertInstanceOf(ValueDumperInterface::class, $this->implementation->getValueDumper());
    }

    public function testGetValueDumperReturnsSameInstanceOnSubsequentCalls(): void
    {
        static::assertSame(
            $this->implementation->getValueDumper(),
            $this->implementation->getValueDumper()
        );
    }

    public function testGetSnapshotWriterReturnsSnapshotWriterInterface(): void
    {
        static::assertInstanceOf(SnapshotWriterInterface::class, $this->implementation->getSnapshotWriter());
    }

    public function testGetSnapshotWriterReturnsSameInstanceOnSubsequentCalls(): void
    {
        static::assertSame(
            $this->implementation->getSnapshotWriter(),
            $this->implementation->getSnapshotWriter()
        );
    }

    public function testGetSnapshotterReturnsSnapshotterInterface(): void
    {
        static::assertInstanceOf(SnapshotterInterface::class, $this->implementation->getSnapshotter());
    }

    public function testGetSnapshotterReturnsSameInstanceOnSubsequentCalls(): void
    {
        static::assertSame(
            $this->implementation->getSnapshotter(),
            $this->implementation->getSnapshotter()
        );
    }
}
