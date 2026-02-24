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

namespace Nytris\Intercooler\Snapshot\Dump;

use Nytris\Intercooler\Snapshot\Statement\StatementBufferInterface;

/**
 * Interface ValueDumperInterface.
 *
 * Recursively converts PHP values to inline PHP expression strings.
 * Adds object/resource creation and property-setup statements to the buffer as side effects.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface ValueDumperInterface
{
    /**
     * Dumps the given value to an inline PHP expression string.
     *
     * For complex values (objects, resources), may add statements to the buffer as a side effect.
     * Returns an inline PHP expression that evaluates to the original value when executed.
     */
    public function dump(
        mixed $value,
        DumpContextInterface $context,
        StatementBufferInterface $buffer
    ): string;
}
