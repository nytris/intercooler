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

use Nytris\Intercooler\Snapshot\SnapshotterInterface;

/**
 * Class Library.
 *
 * Encapsulates an installation of the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Library implements LibraryInterface
{
    public function __construct(
        private readonly SnapshotterInterface $snapshotter
    ) {
    }

    /**
     * @inheritDoc
     */
    public function restore(string $snapshotPath): void
    {
        require $snapshotPath;
    }

    /**
     * @inheritDoc
     */
    public function snapshot(string $snapshotPath): void
    {
        $this->snapshotter->takeSnapshot($snapshotPath);
    }

    /**
     * @inheritDoc
     */
    public function uninstall(): void
    {
    }
}
