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

/**
 * Multi-process snapshot functional test reader script.
 *
 * Restores from the snapshot at the path given as $argv[1],
 * then outputs the restored fixture state as JSON.
 */

require __DIR__ . '/../../../../vendor/autoload.php';

use Nytris\Intercooler\Tests\Functional\MultiProcessSnapshot\Fixtures\FixtureClass;

require $argv[1];

echo json_encode([
    'name' => FixtureClass::$name,
    'count' => FixtureClass::$count,
    'globalVar' => $GLOBALS['ic_mp_test_global'] ?? null,
], JSON_THROW_ON_ERROR);
