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

namespace Nytris\Intercooler\Tests\Unit\Type\Handler;

use Mockery\MockInterface;
use Nytris\Intercooler\Snapshot\Dump\DumpContextInterface;
use Nytris\Intercooler\Snapshot\Dump\ValueDumperInterface;
use Nytris\Intercooler\Snapshot\Statement\StatementBufferInterface;
use Nytris\Intercooler\Tests\AbstractTestCase;
use Nytris\Intercooler\Type\Handler\FileResourceHandler;

/**
 * Class FileResourceHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class FileResourceHandlerTest extends AbstractTestCase
{
    private MockInterface&StatementBufferInterface $buffer;
    private MockInterface&DumpContextInterface $context;
    private FileResourceHandler $handler;
    private MockInterface&ValueDumperInterface $valueDumper;

    public function setUp(): void
    {
        $this->buffer = mock(StatementBufferInterface::class, [
            'addObjectCreation' => null,
        ]);
        $this->context = mock(DumpContextInterface::class, [
            'hasResource' => false,
            'registerResource' => '$__ic_0',
        ]);
        $this->valueDumper = mock(ValueDumperInterface::class);

        $this->handler = new FileResourceHandler();
    }

    public function testCanHandleReturnsTrueForStreamResource(): void
    {
        $resource = fopen('php://memory', 'r+');

        try {
            static::assertTrue($this->handler->canHandle($resource));
        } finally {
            fclose($resource);
        }
    }

    public function testCanHandleReturnsFalseForNonResource(): void
    {
        static::assertFalse($this->handler->canHandle('not a resource'));
    }

    public function testCanHandleReturnsFalseForInteger(): void
    {
        static::assertFalse($this->handler->canHandle(42));
    }

    public function testDumpReturnsRegisteredVarNameForAlreadyRegisteredResource(): void
    {
        $resource = fopen('php://memory', 'r+');

        try {
            $this->context->allows('hasResource')->with($resource)->andReturnTrue();
            $this->context->allows('getResourceVarName')->with($resource)->andReturn('$__ic_existing');

            $result = $this->handler->dump($resource, $this->context, $this->buffer, $this->valueDumper);

            static::assertSame('$__ic_existing', $result);
        } finally {
            fclose($resource);
        }
    }

    public function testDumpRegistersNewResourceAndReturnsVarName(): void
    {
        $resource = fopen('php://memory', 'r+');

        try {
            $this->context->allows('hasResource')->with($resource)->andReturnFalse();
            $this->context->allows('registerResource')->with($resource)->andReturn('$__ic_0');

            $result = $this->handler->dump($resource, $this->context, $this->buffer, $this->valueDumper);

            static::assertSame('$__ic_0', $result);
        } finally {
            fclose($resource);
        }
    }

    public function testDumpAddsCreationStatementWithFopenCall(): void
    {
        $resource = fopen('php://memory', 'r+');

        try {
            $this->buffer->expects('addObjectCreation')
                ->andReturnUsing(function (string $stmt) {
                    static::assertStringContainsString('fopen(', $stmt);
                    static::assertStringContainsString('$__ic_0', $stmt);
                })
                ->once();

            $this->handler->dump($resource, $this->context, $this->buffer, $this->valueDumper);
        } finally {
            fclose($resource);
        }
    }

    public function testDumpAddsSeekCallWhenResourceIsAtNonZeroPosition(): void
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, 'hello world');
        fseek($resource, 5);

        try {
            $capturedStmt = null;
            $this->buffer->expects('addObjectCreation')
                ->andReturnUsing(function (string $stmt) use (&$capturedStmt) {
                    $capturedStmt = $stmt;
                })
                ->once();

            $this->handler->dump($resource, $this->context, $this->buffer, $this->valueDumper);

            static::assertNotNull($capturedStmt);
            static::assertStringContainsString('fseek(', $capturedStmt);
            static::assertStringContainsString('5', $capturedStmt);
        } finally {
            fclose($resource);
        }
    }

    public function testDumpDoesNotAddSeekCallWhenResourceIsAtPositionZero(): void
    {
        $resource = fopen('php://memory', 'r+');

        try {
            $capturedStmt = null;
            $this->buffer->expects('addObjectCreation')
                ->andReturnUsing(function (string $stmt) use (&$capturedStmt) {
                    $capturedStmt = $stmt;
                })
                ->once();

            $this->handler->dump($resource, $this->context, $this->buffer, $this->valueDumper);

            static::assertNotNull($capturedStmt);
            static::assertStringNotContainsString('fseek(', $capturedStmt);
        } finally {
            fclose($resource);
        }
    }

    public function testDumpHandlesStreamWithNoUri(): void
    {
        $resource = fopen('php://memory', 'rb+');

        try {
            $this->context->allows('hasResource')->andReturnFalse();
            $this->context->allows('registerResource')->andReturn('$__ic_0');
            $this->buffer->allows('addObjectCreation');

            $result = $this->handler->dump($resource, $this->context, $this->buffer, $this->valueDumper);

            static::assertSame('$__ic_0', $result);
        } finally {
            fclose($resource);
        }
    }
}
