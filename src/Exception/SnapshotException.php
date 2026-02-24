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

use RuntimeException;

/**
 * Class SnapshotException.
 *
 * Base exception for errors that occur during heap snapshotting.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class SnapshotException extends RuntimeException
{
}
