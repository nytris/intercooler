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

namespace Nytris\Intercooler\Tests\Unit\Snapshot\Heap;

use Nytris\Intercooler\Snapshot\Heap\HeapWalker;
use Nytris\Intercooler\Tests\AbstractTestCase;

// Fixtures for static property tests.
class ClassWithStaticProp
{
    public static string $value = 'initial';
    public static int $count = 0;
}

class ClassWithNoStaticProps
{
    public string $instanceProp = 'hello';
}

class ParentClassWithStaticProp
{
    public static string $parentValue = 'parent';
}

class ChildClassThatInheritsStaticProp extends ParentClassWithStaticProp
{
}

trait TraitWithStaticProp
{
    public static string $traitValue = 'from_trait';
}

class ClassUsingTrait
{
    use TraitWithStaticProp;
}

/**
 * Class HeapWalkerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class HeapWalkerTest extends AbstractTestCase
{
    private HeapWalker $walker;

    public function setUp(): void
    {
        $this->walker = new HeapWalker();
    }

    public function testWalkGlobalsYieldsUserDefinedGlobals(): void
    {
        $GLOBALS['__test_intercooler_global__'] = 'test_value';

        try {
            $globals = iterator_to_array($this->walker->walkGlobals());
            static::assertArrayHasKey('__test_intercooler_global__', $globals);
            static::assertSame('test_value', $globals['__test_intercooler_global__']);
        } finally {
            unset($GLOBALS['__test_intercooler_global__']);
        }
    }

    public function testWalkGlobalsExcludesGlobalsSelfReference(): void
    {
        $globals = iterator_to_array($this->walker->walkGlobals());

        static::assertArrayNotHasKey('GLOBALS', $globals);
    }

    public function testWalkGlobalsExcludesGetSuperglobal(): void
    {
        $globals = iterator_to_array($this->walker->walkGlobals());

        static::assertArrayNotHasKey('_GET', $globals);
    }

    public function testWalkGlobalsExcludesPostSuperglobal(): void
    {
        $globals = iterator_to_array($this->walker->walkGlobals());

        static::assertArrayNotHasKey('_POST', $globals);
    }

    public function testWalkGlobalsExcludesServerSuperglobal(): void
    {
        $globals = iterator_to_array($this->walker->walkGlobals());

        static::assertArrayNotHasKey('_SERVER', $globals);
    }

    public function testWalkGlobalsExcludesCookieSuperglobal(): void
    {
        $globals = iterator_to_array($this->walker->walkGlobals());

        static::assertArrayNotHasKey('_COOKIE', $globals);
    }

    public function testWalkGlobalsExcludesSessionSuperglobal(): void
    {
        $globals = iterator_to_array($this->walker->walkGlobals());

        static::assertArrayNotHasKey('_SESSION', $globals);
    }

    public function testWalkGlobalsYieldsNothingWhenIncludeGlobalsIsFalse(): void
    {
        $GLOBALS['__test_intercooler_global__'] = 'test_value';
        $walker = new HeapWalker(includeGlobals: false);

        try {
            $globals = iterator_to_array($walker->walkGlobals());
            static::assertSame([], $globals);
        } finally {
            unset($GLOBALS['__test_intercooler_global__']);
        }
    }

    public function testWalkClassStaticPropertiesIncludesUserDefinedClassWithStaticProps(): void
    {
        ClassWithStaticProp::$value = 'modified';
        $result = iterator_to_array($this->walker->walkClassStaticProperties());

        static::assertArrayHasKey(ClassWithStaticProp::class, $result);
    }

    public function testWalkClassStaticPropertiesExcludesClassesWithNoStaticProps(): void
    {
        $result = iterator_to_array($this->walker->walkClassStaticProperties());

        static::assertArrayNotHasKey(ClassWithNoStaticProps::class, $result);
    }

    public function testWalkClassStaticPropertiesIncludesStaticPropInDeclaringClass(): void
    {
        $result = iterator_to_array($this->walker->walkClassStaticProperties());

        static::assertArrayHasKey(ParentClassWithStaticProp::class, $result);
    }

    public function testWalkClassStaticPropertiesExcludesInheritedStaticPropFromChildClass(): void
    {
        $result = iterator_to_array($this->walker->walkClassStaticProperties());

        // Child class should not re-declare the parent's static property.
        static::assertArrayNotHasKey(ChildClassThatInheritsStaticProp::class, $result);
    }

    public function testWalkClassStaticPropertiesIncludesTraitStaticPropInHostClass(): void
    {
        $result = iterator_to_array($this->walker->walkClassStaticProperties());

        // The host class (ClassUsingTrait) should include the trait's static property.
        static::assertArrayHasKey(ClassUsingTrait::class, $result);
        $propNames = array_map(fn ($p) => $p->getName(), $result[ClassUsingTrait::class]);
        static::assertContains('traitValue', $propNames);
    }

    public function testWalkClassStaticPropertiesRespectsExcludedClasses(): void
    {
        $walker = new HeapWalker(excludedClasses: [ClassWithStaticProp::class]);

        $result = iterator_to_array($walker->walkClassStaticProperties());

        static::assertArrayNotHasKey(ClassWithStaticProp::class, $result);
    }

    public function testWalkClassStaticPropertiesExcludesInternalPhpClasses(): void
    {
        $result = iterator_to_array($this->walker->walkClassStaticProperties());

        // stdClass is an internal class and should never appear.
        static::assertArrayNotHasKey('stdClass', $result);
    }

    public function testWalkClassStaticPropertiesReturnsReflectionPropertyInstances(): void
    {
        $result = iterator_to_array($this->walker->walkClassStaticProperties());

        $props = $result[ClassWithStaticProp::class];
        static::assertNotEmpty($props);

        foreach ($props as $prop) {
            static::assertInstanceOf(\ReflectionProperty::class, $prop);
        }
    }

    public function testWalkClassStaticPropertiesReturnsCorrectPropertyNames(): void
    {
        $result = iterator_to_array($this->walker->walkClassStaticProperties());

        $propNames = array_map(fn ($p) => $p->getName(), $result[ClassWithStaticProp::class]);
        static::assertContains('value', $propNames);
        static::assertContains('count', $propNames);
    }
}
