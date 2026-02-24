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

namespace Nytris\Intercooler\Snapshot\Statement;

/**
 * Class StatementBuffer.
 *
 * Accumulates generated PHP code statements in four ordered sections:
 * object/resource creation, object property setup, static property setup, and global assignment.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StatementBuffer implements StatementBufferInterface
{
    /**
     * @var string[]
     */
    private array $objectCreationStatements = [];
    /**
     * @var string[]
     */
    private array $objectPropertyStatements = [];
    /**
     * @var string[]
     */
    private array $staticPropertyStatements = [];
    /**
     * @var string[]
     */
    private array $globalStatements = [];

    /**
     * @inheritDoc
     */
    public function addGlobal(string $statement): void
    {
        $this->globalStatements[] = $statement;
    }

    /**
     * @inheritDoc
     */
    public function addObjectCreation(string $statement): void
    {
        $this->objectCreationStatements[] = $statement;
    }

    /**
     * @inheritDoc
     */
    public function addObjectPropertySetup(string $statement): void
    {
        $this->objectPropertyStatements[] = $statement;
    }

    /**
     * @inheritDoc
     */
    public function addStaticPropertySetup(string $statement): void
    {
        $this->staticPropertyStatements[] = $statement;
    }

    /**
     * @inheritDoc
     */
    public function getGlobalStatements(): array
    {
        return $this->globalStatements;
    }

    /**
     * @inheritDoc
     */
    public function getObjectCreationStatements(): array
    {
        return $this->objectCreationStatements;
    }

    /**
     * @inheritDoc
     */
    public function getObjectPropertyStatements(): array
    {
        return $this->objectPropertyStatements;
    }

    /**
     * @inheritDoc
     */
    public function getStaticPropertyStatements(): array
    {
        return $this->staticPropertyStatements;
    }
}
