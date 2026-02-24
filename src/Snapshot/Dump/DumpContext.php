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
 * Class DumpContext.
 *
 * Tracks registered objects and resources across a dump to handle
 * circular references and object identity (shared references).
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class DumpContext implements DumpContextInterface
{
    /**
     * Map from spl_object_id to generated variable name.
     *
     * @var array<int, string>
     */
    private array $objectIdToVarName = [];

    /**
     * Map from get_resource_id() to generated variable name.
     *
     * @var array<int, string>
     */
    private array $resourceIdToVarName = [];

    private int $varCounter = 0;

    /**
     * @inheritDoc
     */
    public function hasObject(object $object): bool
    {
        return array_key_exists(spl_object_id($object), $this->objectIdToVarName);
    }

    /**
     * @inheritDoc
     */
    public function registerObject(object $object): string
    {
        $varName = '$__ic_' . $this->varCounter++;
        $this->objectIdToVarName[spl_object_id($object)] = $varName;

        return $varName;
    }

    /**
     * @inheritDoc
     */
    public function getObjectVarName(object $object): string
    {
        return $this->objectIdToVarName[spl_object_id($object)];
    }

    /**
     * @inheritDoc
     */
    public function getResourceVarName(mixed $resource): string
    {
        return $this->resourceIdToVarName[get_resource_id($resource)];
    }

    /**
     * @inheritDoc
     */
    public function hasResource(mixed $resource): bool
    {
        return array_key_exists(get_resource_id($resource), $this->resourceIdToVarName);
    }

    /**
     * @inheritDoc
     */
    public function registerResource(mixed $resource): string
    {
        $varName = '$__ic_' . $this->varCounter++;
        $this->resourceIdToVarName[get_resource_id($resource)] = $varName;

        return $varName;
    }
}
