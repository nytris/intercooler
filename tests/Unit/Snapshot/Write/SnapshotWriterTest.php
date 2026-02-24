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

namespace Nytris\Intercooler\Tests\Unit\Snapshot\Write;

use Mockery\MockInterface;
use Nytris\Intercooler\Snapshot\Statement\StatementBufferInterface;
use Nytris\Intercooler\Snapshot\Write\SnapshotWriter;
use Nytris\Intercooler\Tests\AbstractTestCase;

/**
 * Class SnapshotWriterTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SnapshotWriterTest extends AbstractTestCase
{
    private MockInterface&StatementBufferInterface $buffer;
    private string $snapshotPath;
    private SnapshotWriter $writer;

    public function setUp(): void
    {
        $this->buffer = mock(StatementBufferInterface::class);
        $this->snapshotPath = sys_get_temp_dir() . '/intercooler_test_snapshot_' . uniqid() . '.php';
        $this->writer = new SnapshotWriter();

        $this->buffer->allows('getObjectCreationStatements')->andReturn([])->byDefault();
        $this->buffer->allows('getObjectPropertyStatements')->andReturn([])->byDefault();
        $this->buffer->allows('getStaticPropertyStatements')->andReturn([])->byDefault();
        $this->buffer->allows('getGlobalStatements')->andReturn([])->byDefault();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->snapshotPath)) {
            unlink($this->snapshotPath);
        }
    }

    public function testWriteCreatesFileAtGivenPath(): void
    {
        $this->writer->write($this->snapshotPath, $this->buffer);

        static::assertFileExists($this->snapshotPath);
    }

    public function testWrittenFileStartsWithPhpOpenTag(): void
    {
        $this->writer->write($this->snapshotPath, $this->buffer);

        $contents = file_get_contents($this->snapshotPath);
        static::assertStringStartsWith('<?php', $contents);
    }

    public function testWrittenFileContainsDeclareStrictTypesStatement(): void
    {
        $this->writer->write($this->snapshotPath, $this->buffer);

        $contents = file_get_contents($this->snapshotPath);
        static::assertStringContainsString('declare(strict_types=1);', $contents);
    }

    public function testWrittenFileContainsSnapshotHeaderComment(): void
    {
        $this->writer->write($this->snapshotPath, $this->buffer);

        $contents = file_get_contents($this->snapshotPath);
        static::assertStringContainsString('Nytris Intercooler heap snapshot', $contents);
        static::assertStringContainsString('Do not edit manually', $contents);
    }

    public function testObjectCreationStatementsAreIncludedInFile(): void
    {
        $this->buffer->allows('getObjectCreationStatements')->andReturn([
            '$__ic_0 = new \\stdClass();',
            '$__ic_1 = new \\Foo();',
        ]);

        $this->writer->write($this->snapshotPath, $this->buffer);

        $contents = file_get_contents($this->snapshotPath);
        static::assertStringContainsString('$__ic_0 = new \\stdClass();', $contents);
        static::assertStringContainsString('$__ic_1 = new \\Foo();', $contents);
        static::assertStringContainsString('Object/resource creation', $contents);
    }

    public function testObjectPropertyStatementsAreIncludedInFile(): void
    {
        $this->buffer->allows('getObjectPropertyStatements')->andReturn([
            '(function ($obj, $v): void { /* ... */ })($__ic_0, 42);',
        ]);

        $this->writer->write($this->snapshotPath, $this->buffer);

        $contents = file_get_contents($this->snapshotPath);
        static::assertStringContainsString('(function ($obj, $v): void { /* ... */ })($__ic_0, 42);', $contents);
        static::assertStringContainsString('Object property setup', $contents);
    }

    public function testStaticPropertyStatementsAreIncludedInFile(): void
    {
        $this->buffer->allows('getStaticPropertyStatements')->andReturn([
            '(function ($v): void { /* ... */ })($__ic_0);',
        ]);

        $this->writer->write($this->snapshotPath, $this->buffer);

        $contents = file_get_contents($this->snapshotPath);
        static::assertStringContainsString('(function ($v): void { /* ... */ })($__ic_0);', $contents);
        static::assertStringContainsString('Static property setup', $contents);
    }

    public function testGlobalStatementsAreIncludedInFile(): void
    {
        $this->buffer->allows('getGlobalStatements')->andReturn([
            '$GLOBALS[\'myVar\'] = 42;',
        ]);

        $this->writer->write($this->snapshotPath, $this->buffer);

        $contents = file_get_contents($this->snapshotPath);
        static::assertStringContainsString('$GLOBALS[\'myVar\'] = 42;', $contents);
        static::assertStringContainsString('Global variable setup', $contents);
    }

    public function testEmptySectionsAreOmittedFromFile(): void
    {
        $this->writer->write($this->snapshotPath, $this->buffer);

        $contents = file_get_contents($this->snapshotPath);
        static::assertStringNotContainsString('Object/resource creation', $contents);
        static::assertStringNotContainsString('Object property setup', $contents);
        static::assertStringNotContainsString('Static property setup', $contents);
        static::assertStringNotContainsString('Global variable setup', $contents);
    }

    public function testSectionsAppearInCorrectOrder(): void
    {
        $this->buffer->allows('getObjectCreationStatements')->andReturn(['creation_stmt']);
        $this->buffer->allows('getObjectPropertyStatements')->andReturn(['prop_stmt']);
        $this->buffer->allows('getStaticPropertyStatements')->andReturn(['static_stmt']);
        $this->buffer->allows('getGlobalStatements')->andReturn(['global_stmt']);

        $this->writer->write($this->snapshotPath, $this->buffer);

        $contents = file_get_contents($this->snapshotPath);
        $creationPos = strpos($contents, 'creation_stmt');
        $propPos = strpos($contents, 'prop_stmt');
        $staticPos = strpos($contents, 'static_stmt');
        $globalPos = strpos($contents, 'global_stmt');

        static::assertLessThan($propPos, $creationPos);
        static::assertLessThan($staticPos, $propPos);
        static::assertLessThan($globalPos, $staticPos);
    }
}
