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
 * Multi-process snapshot functional test writer script.
 *
 * Sets fixture state and global variables, then takes a snapshot to the path given as $argv[1].
 */

require __DIR__ . '/../../../../vendor/autoload.php';

use Nytris\Intercooler\IntercoolerPackage;
use Nytris\Intercooler\Tests\Functional\MultiProcessSnapshot\Fixtures\FixtureClass;

FixtureClass::$name = 'from_snapshot';
FixtureClass::$count = 123;
$GLOBALS['ic_mp_test_global'] = 'global_from_snapshot';

$excludedClasses = array_values(array_filter(
    get_declared_classes(),
    static fn (string $class) => $class !== FixtureClass::class
));

$package = new IntercoolerPackage(
    excludedClasses: $excludedClasses,
    includeGlobals: true
);

$package->getImplementation()->getSnapshotter()->takeSnapshot($argv[1]);
