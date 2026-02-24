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

namespace Nytris\Intercooler\Exception;

/**
 * Class UnsupportedValueTypeException.
 *
 * Raised when a PHP value of an unsupported type is encountered during snapshotting.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class UnsupportedValueTypeException extends SnapshotException
{
    public function __construct(string $type)
    {
        parent::__construct(
            sprintf('Unsupported value type "%s" encountered during heap snapshotting.', $type)
        );
    }
}
