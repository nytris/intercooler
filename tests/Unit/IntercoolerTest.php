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

namespace Nytris\Intercooler\Tests\Unit;

use InvalidArgumentException;
use LogicException;
use Mockery\MockInterface;
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Core\Package\PackageInterface;
use Nytris\Intercooler\Implementation\ImplementationInterface;
use Nytris\Intercooler\Intercooler;
use Nytris\Intercooler\IntercoolerPackageInterface;
use Nytris\Intercooler\Library\LibraryInterface;
use Nytris\Intercooler\Snapshot\SnapshotterInterface;
use Nytris\Intercooler\Tests\AbstractTestCase;

/**
 * Class IntercoolerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class IntercoolerTest extends AbstractTestCase
{
    private MockInterface&LibraryInterface $library;

    public function setUp(): void
    {
        $this->library = mock(LibraryInterface::class, [
            'uninstall' => null,
        ]);

        Intercooler::setLibrary($this->library);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Intercooler::uninstall();
    }

    public function testGetNameReturnsIntercooler(): void
    {
        static::assertSame('intercooler', Intercooler::getName());
    }

    public function testGetVendorReturnsNytris(): void
    {
        static::assertSame('nytris', Intercooler::getVendor());
    }

    public function testIsInstalledReturnsTrueWhenLibraryIsSet(): void
    {
        static::assertTrue(Intercooler::isInstalled());
    }

    public function testIsInstalledReturnsFalseAfterUninstall(): void
    {
        Intercooler::uninstall();

        static::assertFalse(Intercooler::isInstalled());
    }

    public function testGetLibraryReturnsTheCurrentLibrary(): void
    {
        static::assertSame($this->library, Intercooler::getLibrary());
    }

    public function testGetLibraryThrowsWhenNotInstalled(): void
    {
        Intercooler::uninstall();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Library is not installed');

        Intercooler::getLibrary();
    }

    public function testSnapshotDelegatesToLibrary(): void
    {
        $this->library->expects('snapshot')
            ->with('/my/path/snapshot.php')
            ->once();

        Intercooler::snapshot('/my/path/snapshot.php');
    }

    public function testRestoreDelegatesToLibrary(): void
    {
        $this->library->expects('restore')
            ->with('/my/path/snapshot.php')
            ->once();

        Intercooler::restore('/my/path/snapshot.php');
    }

    public function testUninstallCallsLibraryUninstall(): void
    {
        $this->library->expects('uninstall')
            ->once();

        Intercooler::uninstall();
    }

    public function testUninstallSetsLibraryToNull(): void
    {
        Intercooler::uninstall();

        static::assertFalse(Intercooler::isInstalled());
    }

    public function testUninstallDoesNothingWhenNotInstalled(): void
    {
        // Second uninstall should not throw.
        $this->expectNotToPerformAssertions();
        Intercooler::uninstall();
    }

    public function testSetLibraryUninstallsPreviousLibrary(): void
    {
        $newLibrary = mock(LibraryInterface::class);
        $newLibrary->allows('uninstall')->byDefault();

        $this->library->expects('uninstall')
            ->once();

        Intercooler::setLibrary($newLibrary);
    }

    public function testSetLibraryReplacesLibrary(): void
    {
        $newLibrary = mock(LibraryInterface::class);
        $newLibrary->allows('uninstall')->byDefault();
        $this->library->allows('uninstall');

        Intercooler::setLibrary($newLibrary);

        static::assertSame($newLibrary, Intercooler::getLibrary());
    }

    public function testInstallRaisesExceptionForInvalidPackageType(): void
    {
        $packageContext = mock(PackageContextInterface::class);
        $wrongPackage = mock(PackageInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(IntercoolerPackageInterface::class);

        Intercooler::install($packageContext, $wrongPackage);
    }

    public function testInstallCreatesLibraryFromPackageConfiguration(): void
    {
        Intercooler::uninstall();
        $packageContext = mock(PackageContextInterface::class);
        $package = mock(IntercoolerPackageInterface::class);
        $implementation = mock(ImplementationInterface::class);
        $snapshotter = mock(SnapshotterInterface::class);
        $package->allows('getImplementation')->andReturn($implementation);
        $implementation->allows('getSnapshotter')->andReturn($snapshotter);

        Intercooler::install($packageContext, $package);

        static::assertTrue(Intercooler::isInstalled());
    }
}
