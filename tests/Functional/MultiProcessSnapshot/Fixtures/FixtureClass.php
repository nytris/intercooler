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

namespace Nytris\Intercooler\Tests\Functional\MultiProcessSnapshot\Fixtures;

/**
 * Class FixtureClass.
 *
 * A simple class with static properties used as the subject of multi-process snapshot functional tests.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class FixtureClass
{
    public static int $count = 0;
    public static string $name = 'initial';
}
