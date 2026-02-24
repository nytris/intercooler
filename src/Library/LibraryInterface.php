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

namespace Nytris\Intercooler\Library;

/**
 * Interface LibraryInterface.
 *
 * Encapsulates an installation of the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface LibraryInterface
{
    /**
     * Restores the PHP heap from a previously generated snapshot file.
     *
     * Simply includes the snapshot PHP file, which executes the generated restoration code.
     * It is recommended that this method be used in case this behaviour changes in the future.
     */
    public function restore(string $snapshotPath): void;

    /**
     * Snapshots the current PHP heap state to a PHP file at the given path.
     */
    public function snapshot(string $snapshotPath): void;

    /**
     * Uninstalls this installation of the library.
     */
    public function uninstall(): void;
}
