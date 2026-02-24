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
 * Interface StatementBufferInterface.
 *
 * Accumulates generated PHP code statements in four ordered sections:
 * object/resource creation, object property setup, static property setup, and global assignment.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface StatementBufferInterface
{
    /**
     * Adds a statement that assigns a global variable.
     */
    public function addGlobal(string $statement): void;

    /**
     * Adds a statement that creates an object or resource variable.
     * These statements appear first in the snapshot file so that
     * all variables are defined before property-setup code references them.
     */
    public function addObjectCreation(string $statement): void;

    /**
     * Adds a statement that sets an instance property of an object.
     */
    public function addObjectPropertySetup(string $statement): void;

    /**
     * Adds a statement that sets a static property of a class.
     */
    public function addStaticPropertySetup(string $statement): void;

    /**
     * Fetches all global variable assignment statements, in order of addition.
     *
     * @return string[]
     */
    public function getGlobalStatements(): array;

    /**
     * Fetches all object/resource creation statements, in order of addition.
     *
     * @return string[]
     */
    public function getObjectCreationStatements(): array;

    /**
     * Fetches all object property setup statements, in order of addition.
     *
     * @return string[]
     */
    public function getObjectPropertyStatements(): array;

    /**
     * Fetches all static property setup statements, in order of addition.
     *
     * @return string[]
     */
    public function getStaticPropertyStatements(): array;
}
