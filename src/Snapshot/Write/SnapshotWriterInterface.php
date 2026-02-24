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

namespace Nytris\Intercooler\Snapshot\Write;

use Nytris\Intercooler\Snapshot\Statement\StatementBufferInterface;

/**
 * Interface SnapshotWriterInterface.
 *
 * Writes a populated StatementBuffer to a PHP snapshot file on disk.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface SnapshotWriterInterface
{
    /**
     * Writes the given statement buffer to a PHP file at the given path.
     */
    public function write(string $snapshotPath, StatementBufferInterface $buffer): void;
}
