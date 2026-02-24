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

/**
 * Interface DumpContextInterface.
 *
 * Tracks registered objects and resources across a dump to handle
 * circular references and object identity (shared references).
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface DumpContextInterface
{
    /**
     * Determines whether the given object has already been registered.
     */
    public function hasObject(object $object): bool;

    /**
     * Registers an object and returns its unique generated variable name (e.g. `$__ic_0`).
     *
     * Must not be called if the object is already registered; use hasObject() first.
     */
    public function registerObject(object $object): string;

    /**
     * Fetches the generated variable name for an already-registered object.
     */
    public function getObjectVarName(object $object): string;

    /**
     * Fetches the generated variable name for an already-registered resource.
     */
    public function getResourceVarName(mixed $resource): string;

    /**
     * Determines whether the given resource has already been registered.
     */
    public function hasResource(mixed $resource): bool;

    /**
     * Registers a resource and returns its unique generated variable name (e.g. `$__ic_1`).
     *
     * Must not be called if the resource is already registered; use hasResource() first.
     */
    public function registerResource(mixed $resource): string;
}
