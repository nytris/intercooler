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

namespace Nytris\Intercooler\Tests\Unit\Snapshot\Statement;

use Nytris\Intercooler\Snapshot\Statement\StatementBuffer;
use Nytris\Intercooler\Tests\AbstractTestCase;

/**
 * Class StatementBufferTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StatementBufferTest extends AbstractTestCase
{
    private StatementBuffer $buffer;

    public function setUp(): void
    {
        $this->buffer = new StatementBuffer();
    }

    public function testAddObjectCreationAppendsStatement(): void
    {
        $this->buffer->addObjectCreation('$a = new Foo();');

        static::assertSame(['$a = new Foo();'], $this->buffer->getObjectCreationStatements());
    }

    public function testAddObjectCreationAppendsMultipleStatementsInOrder(): void
    {
        $this->buffer->addObjectCreation('$a = new Foo();');
        $this->buffer->addObjectCreation('$b = new Bar();');

        static::assertSame(
            ['$a = new Foo();', '$b = new Bar();'],
            $this->buffer->getObjectCreationStatements()
        );
    }

    public function testAddObjectPropertySetupAppendsStatement(): void
    {
        $this->buffer->addObjectPropertySetup('(function () { /* prop setup */ })();');

        static::assertSame(
            ['(function () { /* prop setup */ })();'],
            $this->buffer->getObjectPropertyStatements()
        );
    }

    public function testAddObjectPropertySetupAppendsMultipleStatementsInOrder(): void
    {
        $this->buffer->addObjectPropertySetup('/* first */');
        $this->buffer->addObjectPropertySetup('/* second */');

        static::assertSame(
            ['/* first */', '/* second */'],
            $this->buffer->getObjectPropertyStatements()
        );
    }

    public function testAddStaticPropertySetupAppendsStatement(): void
    {
        $this->buffer->addStaticPropertySetup('(function () { /* static setup */ })();');

        static::assertSame(
            ['(function () { /* static setup */ })();'],
            $this->buffer->getStaticPropertyStatements()
        );
    }

    public function testAddStaticPropertySetupAppendsMultipleStatementsInOrder(): void
    {
        $this->buffer->addStaticPropertySetup('/* first */');
        $this->buffer->addStaticPropertySetup('/* second */');

        static::assertSame(
            ['/* first */', '/* second */'],
            $this->buffer->getStaticPropertyStatements()
        );
    }

    public function testAddGlobalAppendsStatement(): void
    {
        $this->buffer->addGlobal('$GLOBALS[\'myVar\'] = 42;');

        static::assertSame(
            ['$GLOBALS[\'myVar\'] = 42;'],
            $this->buffer->getGlobalStatements()
        );
    }

    public function testAddGlobalAppendsMultipleStatementsInOrder(): void
    {
        $this->buffer->addGlobal('/* first */');
        $this->buffer->addGlobal('/* second */');

        static::assertSame(
            ['/* first */', '/* second */'],
            $this->buffer->getGlobalStatements()
        );
    }

    public function testDifferentSectionsDontInterfereWithEachOther(): void
    {
        $this->buffer->addObjectCreation('creation');
        $this->buffer->addObjectPropertySetup('prop setup');
        $this->buffer->addStaticPropertySetup('static setup');
        $this->buffer->addGlobal('global');

        static::assertSame(['creation'], $this->buffer->getObjectCreationStatements());
        static::assertSame(['prop setup'], $this->buffer->getObjectPropertyStatements());
        static::assertSame(['static setup'], $this->buffer->getStaticPropertyStatements());
        static::assertSame(['global'], $this->buffer->getGlobalStatements());
    }

    public function testGetObjectCreationStatementsReturnsEmptyArrayInitially(): void
    {
        static::assertSame([], $this->buffer->getObjectCreationStatements());
    }

    public function testGetObjectPropertyStatementsReturnsEmptyArrayInitially(): void
    {
        static::assertSame([], $this->buffer->getObjectPropertyStatements());
    }

    public function testGetStaticPropertyStatementsReturnsEmptyArrayInitially(): void
    {
        static::assertSame([], $this->buffer->getStaticPropertyStatements());
    }

    public function testGetGlobalStatementsReturnsEmptyArrayInitially(): void
    {
        static::assertSame([], $this->buffer->getGlobalStatements());
    }
}
