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
use Nytris\Intercooler\Snapshot\Dump\ValueDumper;
use Nytris\Intercooler\Snapshot\Statement\StatementBuffer;
use Nytris\Intercooler\Tests\AbstractTestCase;
use Nytris\Intercooler\Type\Handler\TypeHandlerInterface;
use stdClass;

// Fixtures for enum tests.
enum MyUnitEnum
{
    case Alpha;
    case Beta;
}

enum MyBackedEnum: string
{
    case Foo = 'foo_value';
    case Bar = 'bar_value';
}

// Fixtures for object tests.
class SimpleObject
{
    public function __construct(
        public string $name = 'default'
    ) {
    }
}

class ObjectWithPrivateProperty
{
    public function __construct(
        private string $secret = 'hidden'
    ) {
    }
}

class ParentObject
{
    public function __construct(
        public string $inherited = 'from_parent'
    ) {
    }
}

class ChildObject extends ParentObject
{
    public function __construct(
        public string $own = 'from_child',
        string $inherited = 'from_parent'
    ) {
        parent::__construct($inherited);
    }
}

/**
 * Class ValueDumperTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ValueDumperTest extends AbstractTestCase
{
    private DumpContext $context;
    private StatementBuffer $buffer;
    private ValueDumper $dumper;

    public function setUp(): void
    {
        $this->context = new DumpContext();
        $this->buffer = new StatementBuffer();
        $this->dumper = new ValueDumper();
    }

    public function testDumpsNull(): void
    {
        static::assertSame('null', $this->dumper->dump(null, $this->context, $this->buffer));
    }

    public function testDumpsTrue(): void
    {
        static::assertSame('true', $this->dumper->dump(true, $this->context, $this->buffer));
    }

    public function testDumpsFalse(): void
    {
        static::assertSame('false', $this->dumper->dump(false, $this->context, $this->buffer));
    }

    public function testDumpsZeroInteger(): void
    {
        static::assertSame('0', $this->dumper->dump(0, $this->context, $this->buffer));
    }

    public function testDumpsPositiveInteger(): void
    {
        static::assertSame('42', $this->dumper->dump(42, $this->context, $this->buffer));
    }

    public function testDumpsNegativeInteger(): void
    {
        static::assertSame('-7', $this->dumper->dump(-7, $this->context, $this->buffer));
    }

    public function testDumpsSimpleFloat(): void
    {
        static::assertSame('3.14', $this->dumper->dump(3.14, $this->context, $this->buffer));
    }

    public function testDumpsWholeNumberFloatWithDecimalPoint(): void
    {
        static::assertSame('1.0', $this->dumper->dump(1.0, $this->context, $this->buffer));
    }

    public function testDumpsNaN(): void
    {
        static::assertSame('NAN', $this->dumper->dump(NAN, $this->context, $this->buffer));
    }

    public function testDumpsPositiveInfinity(): void
    {
        static::assertSame('INF', $this->dumper->dump(INF, $this->context, $this->buffer));
    }

    public function testDumpsNegativeInfinity(): void
    {
        static::assertSame('-INF', $this->dumper->dump(-INF, $this->context, $this->buffer));
    }

    public function testDumpsEmptyString(): void
    {
        static::assertSame("''", $this->dumper->dump('', $this->context, $this->buffer));
    }

    public function testDumpsSimpleString(): void
    {
        static::assertSame("'hello'", $this->dumper->dump('hello', $this->context, $this->buffer));
    }

    public function testDumpsStringWithSingleQuotes(): void
    {
        static::assertSame(
            "'it\\'s a test'",
            $this->dumper->dump("it's a test", $this->context, $this->buffer)
        );
    }

    public function testDumpsEmptyArray(): void
    {
        static::assertSame('[]', $this->dumper->dump([], $this->context, $this->buffer));
    }

    public function testDumpsSimpleListArray(): void
    {
        static::assertSame(
            '[0 => 1, 1 => 2, 2 => 3]',
            $this->dumper->dump([1, 2, 3], $this->context, $this->buffer)
        );
    }

    public function testDumpsAssociativeArray(): void
    {
        static::assertSame(
            "['key' => 'value']",
            $this->dumper->dump(['key' => 'value'], $this->context, $this->buffer)
        );
    }

    public function testDumpsNestedArray(): void
    {
        static::assertSame(
            "['a' => ['b' => 42]]",
            $this->dumper->dump(['a' => ['b' => 42]], $this->context, $this->buffer)
        );
    }

    public function testDumpsClosureAsNullWithComment(): void
    {
        $closure = function () {};

        static::assertSame(
            '/* closure: cannot be snapshotted */ null',
            $this->dumper->dump($closure, $this->context, $this->buffer)
        );
    }

    public function testDumpsUnitEnumInline(): void
    {
        static::assertSame(
            '\\' . MyUnitEnum::class . '::Alpha',
            $this->dumper->dump(MyUnitEnum::Alpha, $this->context, $this->buffer)
        );
    }

    public function testDumpsBackedEnumInline(): void
    {
        static::assertSame(
            '\\' . MyBackedEnum::class . '::from(\'foo_value\')',
            $this->dumper->dump(MyBackedEnum::Foo, $this->context, $this->buffer)
        );
    }

    public function testDumpsStdClassAndRegistersItInContext(): void
    {
        $object = new stdClass();

        $result = $this->dumper->dump($object, $this->context, $this->buffer);

        static::assertSame('$__ic_0', $result);
        static::assertTrue($this->context->hasObject($object));
    }

    public function testDumpsStdClassAddingCreationStatementToBuffer(): void
    {
        $object = new stdClass();

        $this->dumper->dump($object, $this->context, $this->buffer);

        static::assertSame(
            ['$__ic_0 = new \\stdClass();'],
            $this->buffer->getObjectCreationStatements()
        );
    }

    public function testDumpsStdClassWithPropertyAddingPropertySetupToBuffer(): void
    {
        $object = new stdClass();
        $object->name = 'test';

        $this->dumper->dump($object, $this->context, $this->buffer);

        static::assertSame(
            [
                sprintf(
                    '(function ($obj, $v): void { $p = (new \\ReflectionClass(\\%s::class))->getProperty(%s); $p->setValue($obj, $v); })(%s, %s);',
                    stdClass::class,
                    "'name'",
                    '$__ic_0',
                    "'test'"
                ),
            ],
            $this->buffer->getObjectPropertyStatements()
        );
    }

    public function testDumpsTypedObjectWithPublicPropertyAddingCreationStatement(): void
    {
        $object = new SimpleObject('my_name');

        $this->dumper->dump($object, $this->context, $this->buffer);

        static::assertSame(
            [
                sprintf(
                    '$__ic_0 = (new \\ReflectionClass(\\%s::class))->newInstanceWithoutConstructor();',
                    SimpleObject::class
                ),
            ],
            $this->buffer->getObjectCreationStatements()
        );
    }

    public function testDumpsTypedObjectWithPublicPropertyAddingPropertySetup(): void
    {
        $object = new SimpleObject('my_name');

        $this->dumper->dump($object, $this->context, $this->buffer);

        static::assertSame(
            [
                sprintf(
                    '(function ($obj, $v): void { $p = (new \\ReflectionClass(\\%s::class))->getProperty(%s); $p->setValue($obj, $v); })(%s, %s);',
                    SimpleObject::class,
                    "'name'",
                    '$__ic_0',
                    "'my_name'"
                ),
            ],
            $this->buffer->getObjectPropertyStatements()
        );
    }

    public function testDumpsObjectWithPrivatePropertyAddingPropertySetup(): void
    {
        $object = new ObjectWithPrivateProperty('my_secret');

        $this->dumper->dump($object, $this->context, $this->buffer);

        static::assertSame(
            [
                sprintf(
                    '(function ($obj, $v): void { $p = (new \\ReflectionClass(\\%s::class))->getProperty(%s); $p->setValue($obj, $v); })(%s, %s);',
                    ObjectWithPrivateProperty::class,
                    "'secret'",
                    '$__ic_0',
                    "'my_secret'"
                ),
            ],
            $this->buffer->getObjectPropertyStatements()
        );
    }

    public function testHandlesCircularObjectReferenceByReturningExistingVarName(): void
    {
        $object = new stdClass();
        $object->self = $object; // Circular reference.

        $result = $this->dumper->dump($object, $this->context, $this->buffer);

        // The circular reference should result in the same var name being used inline.
        static::assertSame('$__ic_0', $result);

        $propertyStatements = $this->buffer->getObjectPropertyStatements();
        static::assertCount(1, $propertyStatements);
        static::assertStringContainsString('$__ic_0', $propertyStatements[0]);
        // The self-reference should use the same var name as the object.
        static::assertStringContainsString('$__ic_0, $__ic_0', $propertyStatements[0]);
    }

    public function testDumpsObjectOnlyOnceWhenReferencedMultipleTimes(): void
    {
        $shared = new stdClass();
        $shared->val = 1;

        $container = new stdClass();
        $container->a = $shared;
        $container->b = $shared;

        $this->dumper->dump($container, $this->context, $this->buffer);

        // $shared (inner) should be registered once, then referenced by var name for both a and b.
        static::assertCount(2, $this->buffer->getObjectCreationStatements());
    }

    public function testDumpsClosurePropertyWithComment(): void
    {
        $object = new stdClass();
        $object->fn = function () {};

        $this->dumper->dump($object, $this->context, $this->buffer);

        $propStatements = $this->buffer->getObjectPropertyStatements();
        static::assertCount(1, $propStatements);
        static::assertStringContainsString('closure', $propStatements[0]);
        static::assertStringContainsString('cannot be snapshotted', $propStatements[0]);
    }

    public function testDumpsArrayContainingObject(): void
    {
        $object = new stdClass();
        $object->x = 1;

        $result = $this->dumper->dump(['item' => $object], $this->context, $this->buffer);

        // The object is registered as $__ic_0 and referenced inline in the array literal.
        static::assertSame("['item' => \$__ic_0]", $result);
    }

    public function testCustomTypeHandlerIsTriedBeforeBuiltInHandling(): void
    {
        $handler = mock(TypeHandlerInterface::class);
        $dumper = new ValueDumper([$handler]);
        $value = 42;

        $handler->allows('canHandle')->with($value)->andReturnTrue();
        $handler->allows('dump')
            ->with($value, $this->context, $this->buffer, $dumper)
            ->andReturn("'custom_output'");

        $result = $dumper->dump($value, $this->context, $this->buffer);

        static::assertSame("'custom_output'", $result);
    }

    public function testCustomTypeHandlerIsSkippedWhenItCannotHandleValue(): void
    {
        $handler = mock(TypeHandlerInterface::class);
        $dumper = new ValueDumper([$handler]);

        $handler->allows('canHandle')->andReturnFalse();

        $result = $dumper->dump(42, $this->context, $this->buffer);

        static::assertSame('42', $result);
    }

    public function testDumpsUnsupportedResourceTypeWithComment(): void
    {
        // Use an already-closed resource (it becomes a "resource (closed)" type in PHP).
        // We test the fallback path by providing a non-stream resource.
        // Since it is hard to create arbitrary non-stream resources in tests,
        // we verify that stream resources fall through when no custom handler is installed.
        $resource = fopen('php://memory', 'rb+');

        try {
            // Without a FileResourceHandler, streams fall back to the default comment.
            $result = $this->dumper->dump($resource, $this->context, $this->buffer);

            static::assertStringContainsString('unsupported resource type', $result);
            static::assertStringContainsString('null', $result);
        } finally {
            fclose($resource);
        }
    }
}
