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

namespace Nytris\Intercooler;

use Nytris\Core\Package\PackageInterface;
use Nytris\Intercooler\Implementation\ImplementationInterface;
use Nytris\Intercooler\Type\Handler\TypeHandlerInterface;

/**
 * Interface IntercoolerPackageInterface.
 *
 * Configures the installation of Nytris Intercooler.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface IntercoolerPackageInterface extends PackageInterface
{
    /**
     * Fetches additional type handlers, applied before the built-in handlers.
     *
     * @return TypeHandlerInterface[]
     */
    public function getAdditionalTypeHandlers(): array;

    /**
     * Fetches the FQCNs of classes to exclude from static property snapshotting.
     *
     * @return string[]
     */
    public function getExcludedClasses(): array;

    /**
     * Fetches the implementation to use for wiring up Intercooler's service objects.
     *
     * Developers may override this to return a custom implementation.
     */
    public function getImplementation(): ImplementationInterface;

    /**
     * Determines whether global variables should be included in the snapshot.
     */
    public function shouldIncludeGlobals(): bool;
}
