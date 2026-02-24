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

namespace Nytris\Intercooler\Tests\Functional\MultiProcessSnapshot;

use Nytris\Intercooler\Tests\Functional\AbstractFunctionalTestCase;
use Symfony\Component\Process\Process;

/**
 * Class MultiProcessSnapshotFunctionalTest.
 *
 * Multi-process functional tests for snapshotting and restoring heap state.
 * A writer PHP subprocess sets fixture state and takes a snapshot;
 * a subsequent reader subprocess restores from the snapshot and outputs the state as JSON.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class MultiProcessSnapshotFunctionalTest extends AbstractFunctionalTestCase
{
    private string $varPath;
    private string $snapshotPath;

    public function setUp(): void
    {
        $this->varPath = dirname(__DIR__, 3) . '/var/test';
        @mkdir($this->varPath, recursive: true);

        $this->snapshotPath = $this->varPath . '/snapshot.php';
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->rimrafDescendantsOf($this->varPath);
    }

    public function testWriterCreatesSnapshotFile(): void
    {
        $writer = new Process(['php', __DIR__ . '/Fixtures/writer.php', $this->snapshotPath]);
        $writer->mustRun();

        static::assertFileExists($this->snapshotPath);
    }

    public function testReaderRestoresStaticStringPropertyFromSnapshot(): void
    {
        $writer = new Process(['php', __DIR__ . '/Fixtures/writer.php', $this->snapshotPath]);
        $writer->mustRun();

        $reader = new Process(['php', __DIR__ . '/Fixtures/reader.php', $this->snapshotPath]);
        $reader->mustRun();

        $result = json_decode($reader->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        static::assertSame('from_snapshot', $result['name']);
    }

    public function testReaderRestoresStaticIntPropertyFromSnapshot(): void
    {
        $writer = new Process(['php', __DIR__ . '/Fixtures/writer.php', $this->snapshotPath]);
        $writer->mustRun();

        $reader = new Process(['php', __DIR__ . '/Fixtures/reader.php', $this->snapshotPath]);
        $reader->mustRun();

        $result = json_decode($reader->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        static::assertSame(123, $result['count']);
    }

    public function testReaderRestoresGlobalVariableFromSnapshot(): void
    {
        $writer = new Process(['php', __DIR__ . '/Fixtures/writer.php', $this->snapshotPath]);
        $writer->mustRun();

        $reader = new Process(['php', __DIR__ . '/Fixtures/reader.php', $this->snapshotPath]);
        $reader->mustRun();

        $result = json_decode($reader->getOutput(), true, flags: JSON_THROW_ON_ERROR);

        static::assertSame('global_from_snapshot', $result['globalVar']);
    }
}
