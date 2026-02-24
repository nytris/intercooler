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

namespace Nytris\Intercooler\Snapshot;

use Nytris\Intercooler\Snapshot\Dump\DumpContext;
use Nytris\Intercooler\Snapshot\Dump\DumpContextInterface;
use Nytris\Intercooler\Snapshot\Statement\StatementBuffer;
use Nytris\Intercooler\Snapshot\Statement\StatementBufferInterface;

/**
 * Interface SnapshotterInterface.
 *
 * Orchestrates the snapshotting of the current PHP heap to a PHP file.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface SnapshotterInterface
{
    /**
     * Snapshots the current PHP heap state to a PHP file at the given path.
     *
     * Iterates all GC roots (class static properties and global variables),
     * recursively dumps all reachable values to PHP code, and writes the result
     * to the given file. Including the generated file on a subsequent request
     * restores the heap state exactly as it was at snapshot time.
     */
    public function takeSnapshot(
        string $snapshotPath,
        DumpContextInterface $context = new DumpContext(),
        StatementBufferInterface $buffer = new StatementBuffer()
    ): void;
}
