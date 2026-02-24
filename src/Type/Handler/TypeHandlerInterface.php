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

namespace Nytris\Intercooler\Type\Handler;

use Nytris\Intercooler\Snapshot\Dump\DumpContextInterface;
use Nytris\Intercooler\Snapshot\Dump\ValueDumperInterface;
use Nytris\Intercooler\Snapshot\Statement\StatementBufferInterface;

/**
 * Interface TypeHandlerInterface.
 *
 * Pluggable handler for snapshotting specific types of PHP values.
 * Allows customisation of the generated PHP code for special value types.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface TypeHandlerInterface
{
    /**
     * Determines whether this handler can snapshot the given value.
     */
    public function canHandle(mixed $value): bool;

    /**
     * Dumps the given value to an inline PHP expression string.
     *
     * May add statements to the buffer as a side effect (e.g. for variable definitions).
     * Returns an inline PHP expression (e.g. a literal or a variable name).
     */
    public function dump(
        mixed $value,
        DumpContextInterface $context,
        StatementBufferInterface $buffer,
        ValueDumperInterface $valueDumper
    ): string;
}
