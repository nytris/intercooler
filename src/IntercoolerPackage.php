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

use Nytris\Intercooler\Implementation\Implementation;
use Nytris\Intercooler\Implementation\ImplementationInterface;
use Nytris\Intercooler\Type\Handler\TypeHandlerInterface;

/**
 * Class IntercoolerPackage.
 *
 * Configures the installation of Nytris Intercooler.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class IntercoolerPackage implements IntercoolerPackageInterface
{
    /**
     * @param TypeHandlerInterface[] $additionalTypeHandlers Additional handlers, tried before built-in ones.
     * @param string[] $excludedClasses FQCNs of classes to exclude from static property snapshotting.
     * @param bool $includeGlobals Whether to include global variables in the snapshot.
     */
    public function __construct(
        private readonly array $additionalTypeHandlers = [],
        private readonly array $excludedClasses = [],
        private readonly bool $includeGlobals = true
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getAdditionalTypeHandlers(): array
    {
        return $this->additionalTypeHandlers;
    }

    /**
     * @inheritDoc
     */
    public function getExcludedClasses(): array
    {
        return $this->excludedClasses;
    }

    /**
     * @inheritDoc
     */
    public function getImplementation(): ImplementationInterface
    {
        return new Implementation($this);
    }

    /**
     * @inheritDoc
     */
    public function getPackageFacadeFqcn(): string
    {
        return Intercooler::class;
    }

    /**
     * @inheritDoc
     */
    public function shouldIncludeGlobals(): bool
    {
        return $this->includeGlobals;
    }
}
