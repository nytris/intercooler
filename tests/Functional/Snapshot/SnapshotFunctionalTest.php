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

namespace Nytris\Intercooler\Tests\Functional\Snapshot;

use Nytris\Intercooler\IntercoolerPackage;
use Nytris\Intercooler\Snapshot\SnapshotterInterface;
use Nytris\Intercooler\Tests\Functional\AbstractFunctionalTestCase;
use Nytris\Intercooler\Tests\Functional\Snapshot\Fixtures\FixtureClass;

/**
 * Class SnapshotFunctionalTest.
 *
 * In-process functional tests for snapshotting and restoring heap state.
 * Uses the Implementation class directly to wire up real services without going
 * through the Intercooler facade.
 *
 * The snapshot is scoped to only the fixture class (all other declared classes are
 * excluded) to avoid interfering with PHPUnit's runtime state.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SnapshotFunctionalTest extends AbstractFunctionalTestCase
{
    private string $varPath;
    private string $snapshotPath;

    public function setUp(): void
    {
        $this->varPath = dirname(__DIR__, 3) . '/var/test';
        @mkdir($this->varPath, recursive: true);

        $this->snapshotPath = $this->varPath . '/snapshot.php';

        FixtureClass::$name = 'initial';
        FixtureClass::$count = 0;
    }

    public function tearDown(): void
    {
        parent::tearDown();

        // Reset fixture state after each test.
        FixtureClass::$name = 'initial';
        FixtureClass::$count = 0;

        $this->rimrafDescendantsOf($this->varPath);
    }

    public function testSnapshotWritesPhpFile(): void
    {
        $snapshotter = $this->createSnapshotter();

        $snapshotter->takeSnapshot($this->snapshotPath);

        static::assertFileExists($this->snapshotPath);
    }

    public function testRestoreRestoresStaticStringProperty(): void
    {
        FixtureClass::$name = 'original';

        $snapshotter = $this->createSnapshotter();
        $snapshotter->takeSnapshot($this->snapshotPath);

        FixtureClass::$name = 'modified';

        require $this->snapshotPath;

        static::assertSame('original', FixtureClass::$name);
    }

    public function testRestoreRestoresStaticIntProperty(): void
    {
        FixtureClass::$count = 42;

        $snapshotter = $this->createSnapshotter();
        $snapshotter->takeSnapshot($this->snapshotPath);

        FixtureClass::$count = 99;

        require $this->snapshotPath;

        static::assertSame(42, FixtureClass::$count);
    }

    public function testRestoreRestoresMultipleStaticPropertiesTogether(): void
    {
        FixtureClass::$name = 'combined';
        FixtureClass::$count = 7;

        $snapshotter = $this->createSnapshotter();
        $snapshotter->takeSnapshot($this->snapshotPath);

        FixtureClass::$name = 'overwritten';
        FixtureClass::$count = 0;

        require $this->snapshotPath;

        static::assertSame('combined', FixtureClass::$name);
        static::assertSame(7, FixtureClass::$count);
    }

    public function testRestoreRestoresGlobalVariable(): void
    {
        $GLOBALS['ic_functional_test_global'] = 'snapshotted_value';

        $snapshotter = $this->createSnapshotter(includeGlobals: true);
        $snapshotter->takeSnapshot($this->snapshotPath);

        $GLOBALS['ic_functional_test_global'] = 'overwritten';

        require $this->snapshotPath;

        static::assertSame('snapshotted_value', $GLOBALS['ic_functional_test_global']);

        unset($GLOBALS['ic_functional_test_global']);
    }

    /**
     * Creates a snapshotter scoped to only the fixture class so the snapshot
     * does not interfere with PHPUnit's runtime state.
     */
    private function createSnapshotter(bool $includeGlobals = false): SnapshotterInterface
    {
        $excludedClasses = array_values(array_filter(
            get_declared_classes(),
            static fn (string $class) => $class !== FixtureClass::class
        ));

        $package = new IntercoolerPackage(
            excludedClasses: $excludedClasses,
            includeGlobals: $includeGlobals
        );

        return $package->getImplementation()->getSnapshotter();
    }
}
