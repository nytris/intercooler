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

namespace Nytris\Intercooler;

use InvalidArgumentException;
use LogicException;
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Core\Package\PackageInterface;
use Nytris\Intercooler\Library\Library;
use Nytris\Intercooler\Library\LibraryInterface;

/**
 * Class Intercooler.
 *
 * Defines the public facade API for the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Intercooler implements IntercoolerInterface
{
    private static ?LibraryInterface $library = null;

    /**
     * @inheritDoc
     */
    public static function getLibrary(): LibraryInterface
    {
        if (!self::$library) {
            throw new LogicException(
                'Library is not installed - did you forget to install this package in nytris.config.php?'
            );
        }

        return self::$library;
    }

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return 'intercooler';
    }

    /**
     * @inheritDoc
     */
    public static function getVendor(): string
    {
        return 'nytris';
    }

    /**
     * @inheritDoc
     */
    public static function install(PackageContextInterface $packageContext, PackageInterface $package): void
    {
        if (!$package instanceof IntercoolerPackageInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Package config must be a %s but it was a %s',
                    IntercoolerPackageInterface::class,
                    $package::class
                )
            );
        }

        self::$library = new Library($package->getImplementation()->getSnapshotter());
    }

    /**
     * @inheritDoc
     */
    public static function isInstalled(): bool
    {
        return self::$library !== null;
    }

    /**
     * @inheritDoc
     */
    public static function restore(string $snapshotPath): void
    {
        self::getLibrary()->restore($snapshotPath);
    }

    /**
     * @inheritDoc
     */
    public static function setLibrary(LibraryInterface $library): void
    {
        if (self::$library !== null) {
            self::$library->uninstall();
        }

        self::$library = $library;
    }

    /**
     * @inheritDoc
     */
    public static function snapshot(string $snapshotPath): void
    {
        self::getLibrary()->snapshot($snapshotPath);
    }

    /**
     * @inheritDoc
     */
    public static function uninstall(): void
    {
        if (self::$library === null) {
            // Not yet installed; nothing to do.
            return;
        }

        self::$library->uninstall();
        self::$library = null;
    }
}
