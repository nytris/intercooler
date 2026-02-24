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

namespace Nytris\Intercooler\Tests\Unit\Library;

use Mockery\MockInterface;
use Nytris\Intercooler\Library\Library;
use Nytris\Intercooler\Snapshot\SnapshotterInterface;
use Nytris\Intercooler\Tests\AbstractTestCase;

/**
 * Class LibraryTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class LibraryTest extends AbstractTestCase
{
    private Library $library;
    private MockInterface&SnapshotterInterface $snapshotter;

    public function setUp(): void
    {
        $this->snapshotter = mock(SnapshotterInterface::class);

        $this->library = new Library($this->snapshotter);
    }

    public function testSnapshotDelegatesToSnapshotter(): void
    {
        $this->snapshotter->expects('takeSnapshot')
            ->with('/my/snapshot.php')
            ->once();

        $this->library->snapshot('/my/snapshot.php');
    }

    public function testRestoreIncludesSnapshotFile(): void
    {
        // Create a temporary snapshot file that sets a known global.
        $snapshotPath = sys_get_temp_dir() . '/intercooler_restore_test_' . uniqid() . '.php';
        file_put_contents($snapshotPath, '<?php $GLOBALS[\'__ic_restore_test__\'] = \'restored\';');

        try {
            $this->library->restore($snapshotPath);

            static::assertSame('restored', $GLOBALS['__ic_restore_test__']);
        } finally {
            unset($GLOBALS['__ic_restore_test__']);
            unlink($snapshotPath);
        }
    }

    public function testUninstallDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        $this->library->uninstall();
    }
}
