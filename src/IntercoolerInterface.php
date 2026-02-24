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

use Nytris\Core\Package\PackageFacadeInterface;
use Nytris\Intercooler\Library\LibraryInterface;

/**
 * Interface IntercoolerInterface.
 *
 * Defines the public facade API for the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface IntercoolerInterface extends PackageFacadeInterface
{
    /**
     * Fetches the current library installation.
     */
    public static function getLibrary(): LibraryInterface;

    /**
     * Restores the PHP heap from a previously generated snapshot file at the given path.
     */
    public static function restore(string $snapshotPath): void;

    /**
     * Overrides the current library installation with the given one.
     */
    public static function setLibrary(LibraryInterface $library): void;

    /**
     * Snapshots the current PHP heap state to a PHP file at the given path.
     */
    public static function snapshot(string $snapshotPath): void;
}
