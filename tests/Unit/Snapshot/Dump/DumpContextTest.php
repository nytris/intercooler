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

namespace Nytris\Intercooler\Tests\Unit\Snapshot\Dump;

use Nytris\Intercooler\Snapshot\Dump\DumpContext;
use Nytris\Intercooler\Tests\AbstractTestCase;
use stdClass;

/**
 * Class DumpContextTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class DumpContextTest extends AbstractTestCase
{
    private DumpContext $context;

    public function setUp(): void
    {
        $this->context = new DumpContext();
    }

    public function testHasObjectReturnsFalseForUnregisteredObject(): void
    {
        $object = new stdClass();

        static::assertFalse($this->context->hasObject($object));
    }

    public function testHasObjectReturnsTrueAfterRegistering(): void
    {
        $object = new stdClass();
        $this->context->registerObject($object);

        static::assertTrue($this->context->hasObject($object));
    }

    public function testRegisterObjectReturnsGeneratedVariableName(): void
    {
        $object = new stdClass();

        $varName = $this->context->registerObject($object);

        static::assertSame('$__ic_0', $varName);
    }

    public function testRegisterObjectIncrementsCounterForEachObject(): void
    {
        $object1 = new stdClass();
        $object2 = new stdClass();

        $varName1 = $this->context->registerObject($object1);
        $varName2 = $this->context->registerObject($object2);

        static::assertSame('$__ic_0', $varName1);
        static::assertSame('$__ic_1', $varName2);
    }

    public function testGetObjectVarNameReturnsRegisteredVarName(): void
    {
        $object = new stdClass();
        $this->context->registerObject($object);

        static::assertSame('$__ic_0', $this->context->getObjectVarName($object));
    }

    public function testGetObjectVarNameDistinguishesBetweenObjects(): void
    {
        $object1 = new stdClass();
        $object2 = new stdClass();

        $this->context->registerObject($object1);
        $this->context->registerObject($object2);

        static::assertSame('$__ic_0', $this->context->getObjectVarName($object1));
        static::assertSame('$__ic_1', $this->context->getObjectVarName($object2));
    }

    public function testHasResourceReturnsFalseForUnregisteredResource(): void
    {
        $resource = fopen('php://memory', 'rb+');

        try {
            static::assertFalse($this->context->hasResource($resource));
        } finally {
            fclose($resource);
        }
    }

    public function testHasResourceReturnsTrueAfterRegistering(): void
    {
        $resource = fopen('php://memory', 'rb+');

        try {
            $this->context->registerResource($resource);

            static::assertTrue($this->context->hasResource($resource));
        } finally {
            fclose($resource);
        }
    }

    public function testRegisterResourceReturnsGeneratedVariableName(): void
    {
        $resource = fopen('php://memory', 'rb+');

        try {
            $varName = $this->context->registerResource($resource);

            static::assertSame('$__ic_0', $varName);
        } finally {
            fclose($resource);
        }
    }

    public function testRegisterResourceSharesCounterWithObjects(): void
    {
        $object = new stdClass();
        $resource = fopen('php://memory', 'rb+');

        try {
            $this->context->registerObject($object);
            $varName = $this->context->registerResource($resource);

            static::assertSame('$__ic_1', $varName);
        } finally {
            fclose($resource);
        }
    }

    public function testGetResourceVarNameReturnsRegisteredVarName(): void
    {
        $resource = fopen('php://memory', 'rb+');

        try {
            $this->context->registerResource($resource);

            static::assertSame('$__ic_0', $this->context->getResourceVarName($resource));
        } finally {
            fclose($resource);
        }
    }
}
