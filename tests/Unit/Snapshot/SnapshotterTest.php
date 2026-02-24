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

namespace Nytris\Intercooler\Tests\Unit\Snapshot;

use Mockery;
use Mockery\MockInterface;
use Nytris\Intercooler\Snapshot\Dump\ValueDumperInterface;
use Nytris\Intercooler\Snapshot\Heap\HeapWalkerInterface;
use Nytris\Intercooler\Snapshot\Snapshotter;
use Nytris\Intercooler\Snapshot\Statement\StatementBufferInterface;
use Nytris\Intercooler\Snapshot\Write\SnapshotWriterInterface;
use Nytris\Intercooler\Tests\AbstractTestCase;
use ReflectionProperty;

/**
 * Class SnapshotterTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SnapshotterTest extends AbstractTestCase
{
    private MockInterface&HeapWalkerInterface $heapWalker;
    private Snapshotter $snapshotter;
    private string $snapshotPath;
    private MockInterface&ValueDumperInterface $valueDumper;
    private MockInterface&SnapshotWriterInterface $snapshotWriter;

    public function setUp(): void
    {
        $this->heapWalker = mock(HeapWalkerInterface::class);
        $this->valueDumper = mock(ValueDumperInterface::class);
        $this->snapshotWriter = mock(SnapshotWriterInterface::class);
        $this->snapshotPath = '/tmp/test_snapshot.php';

        $this->heapWalker->allows('walkClassStaticProperties')->andReturn([])->byDefault();
        $this->heapWalker->allows('walkGlobals')->andReturn([])->byDefault();
        $this->snapshotWriter->allows('write')->byDefault();
        $this->valueDumper->allows('dump')->andReturn('null')->byDefault();

        $this->snapshotter = new Snapshotter(
            $this->heapWalker,
            $this->valueDumper,
            $this->snapshotWriter
        );
    }

    public function testTakeSnapshotCallsSnapshotWriter(): void
    {
        $this->snapshotWriter->expects('write')
            ->with($this->snapshotPath, Mockery::type(StatementBufferInterface::class))
            ->once();

        $this->snapshotter->takeSnapshot($this->snapshotPath);
    }

    public function testTakeSnapshotWalksClassStaticProperties(): void
    {
        $this->heapWalker->expects('walkClassStaticProperties')
            ->andReturn([])
            ->once();

        $this->snapshotter->takeSnapshot($this->snapshotPath);
    }

    public function testTakeSnapshotWalksGlobals(): void
    {
        $this->heapWalker->expects('walkGlobals')
            ->andReturn([])
            ->once();

        $this->snapshotter->takeSnapshot($this->snapshotPath);
    }

    public function testTakeSnapshotAddsStaticPropertySetupToBuffer(): void
    {
        $property = mock(ReflectionProperty::class, [
            'getName' => 'myProp',
            'isInitialized' => true,
            'getValue' => 'myValue',
        ]);
        $this->heapWalker->allows('walkClassStaticProperties')->andReturn(
            ['MyClass' => [$property]]
        );
        $this->valueDumper->allows('dump')
            ->with('myValue', Mockery::type('object'), Mockery::type(StatementBufferInterface::class))
            ->andReturn("'myValue'");
        $capturedBuffer = null;
        $this->snapshotWriter->allows('write')
            ->andReturnUsing(function (string $path, StatementBufferInterface $buffer) use (&$capturedBuffer) {
                $capturedBuffer = $buffer;
            });

        $this->snapshotter->takeSnapshot($this->snapshotPath);

        static::assertNotNull($capturedBuffer);
        $staticStatements = $capturedBuffer->getStaticPropertyStatements();
        static::assertCount(1, $staticStatements);
        static::assertStringContainsString('MyClass', $staticStatements[0]);
        static::assertStringContainsString("'myProp'", $staticStatements[0]);
        static::assertStringContainsString("'myValue'", $staticStatements[0]);
    }

    public function testTakeSnapshotSkipsUninitializedStaticProperties(): void
    {
        $property = mock(ReflectionProperty::class, [
            'getName' => 'uninitProp',
            'isInitialized' => false,
        ]);
        $this->heapWalker->allows('walkClassStaticProperties')->andReturn(
            ['MyClass' => [$property]]
        );
        $capturedBuffer = null;
        $this->snapshotWriter->allows('write')
            ->andReturnUsing(function (string $path, StatementBufferInterface $buffer) use (&$capturedBuffer) {
                $capturedBuffer = $buffer;
            });

        $this->snapshotter->takeSnapshot($this->snapshotPath);

        static::assertNotNull($capturedBuffer);
        static::assertSame([], $capturedBuffer->getStaticPropertyStatements());
    }

    public function testTakeSnapshotAddsGlobalToBuffer(): void
    {
        $this->heapWalker->allows('walkGlobals')->andReturn(['myGlobal' => 'globalValue']);
        $this->valueDumper->allows('dump')
            ->with('globalValue', Mockery::type('object'), Mockery::type(StatementBufferInterface::class))
            ->andReturn("'globalValue'");
        $capturedBuffer = null;
        $this->snapshotWriter->allows('write')
            ->andReturnUsing(function (string $path, StatementBufferInterface $buffer) use (&$capturedBuffer) {
                $capturedBuffer = $buffer;
            });

        $this->snapshotter->takeSnapshot($this->snapshotPath);

        static::assertNotNull($capturedBuffer);
        $globalStatements = $capturedBuffer->getGlobalStatements();
        static::assertCount(1, $globalStatements);
        static::assertStringContainsString("'myGlobal'", $globalStatements[0]);
        static::assertStringContainsString("'globalValue'", $globalStatements[0]);
    }

    public function testTakeSnapshotPassesCorrectSnapshotPathToWriter(): void
    {
        $customPath = '/custom/path/snapshot.php';

        $this->snapshotWriter->expects('write')
            ->with($customPath, Mockery::type(StatementBufferInterface::class))
            ->once();

        $this->snapshotter->takeSnapshot($customPath);
    }
}
