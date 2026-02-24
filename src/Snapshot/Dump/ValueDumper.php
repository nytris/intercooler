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

use BackedEnum;
use Closure;
use ReflectionObject;
use Nytris\Intercooler\Exception\UnsupportedValueTypeException;
use Nytris\Intercooler\Snapshot\Statement\StatementBufferInterface;
use Nytris\Intercooler\Type\Handler\TypeHandlerInterface;
use UnitEnum;
use stdClass;

/**
 * Class ValueDumper.
 *
 * Recursively converts PHP values to inline PHP expression strings.
 * Adds object/resource creation and property-setup statements to the buffer as side effects.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class ValueDumper implements ValueDumperInterface
{
    /**
     * @param TypeHandlerInterface[] $typeHandlers
     */
    public function __construct(
        private readonly array $typeHandlers = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function dump(
        mixed $value,
        DumpContextInterface $context,
        StatementBufferInterface $buffer
    ): string {
        // Attempt custom type handlers first.
        foreach ($this->typeHandlers as $handler) {
            if ($handler->canHandle($value)) {
                return $handler->dump($value, $context, $buffer, $this);
            }
        }

        return match (true) {
            is_null($value) => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value) => (string) $value,
            is_float($value) => $this->dumpFloat($value),
            is_string($value) => var_export($value, true),
            is_array($value) => $this->dumpArray($value, $context, $buffer),
            // TODO: Handle better.
            $value instanceof Closure => '/* closure: cannot be snapshotted */ null',
            $value instanceof BackedEnum => $this->dumpBackedEnum($value),
            $value instanceof UnitEnum => $this->dumpUnitEnum($value),
            is_object($value) => $this->dumpObject($value, $context, $buffer),
            is_resource($value) => $this->dumpResource($value),
            default => throw new UnsupportedValueTypeException(gettype($value)),
        };
    }

    /**
     * Dumps a float value to an inline PHP expression.
     */
    private function dumpFloat(float $value): string
    {
        if (is_nan($value)) {
            return 'NAN';
        }

        if (is_infinite($value)) {
            return $value > 0 ? 'INF' : '-INF';
        }

        $str = (string) $value;

        // Ensure the string representation is unambiguously a float.
        if (!str_contains($str, '.') && !str_contains($str, 'E') && !str_contains($str, 'e')) {
            $str .= '.0';
        }

        return $str;
    }

    /**
     * Dumps an array value to an inline PHP expression.
     *
     * @param array<mixed> $value
     */
    private function dumpArray(
        array $value,
        DumpContextInterface $context,
        StatementBufferInterface $buffer
    ): string {
        if (empty($value)) {
            return '[]';
        }

        $items = [];

        foreach ($value as $k => $v) {
            $items[] = var_export($k, true) . ' => ' . $this->dump($v, $context, $buffer);
        }

        return '[' . implode(', ', $items) . ']';
    }

    /**
     * Dumps a backed enum value to an inline PHP expression.
     */
    private function dumpBackedEnum(BackedEnum $value): string
    {
        $class = $value::class;

        return '\\' . $class . '::from(' . var_export($value->value, true) . ')';
    }

    /**
     * Dumps a unit enum value to an inline PHP expression.
     */
    private function dumpUnitEnum(UnitEnum $value): string
    {
        $class = $value::class;

        return '\\' . $class . '::' . $value->name;
    }

    /**
     * Dumps an object value to a generated variable name,
     * adding creation and property-setup statements to the buffer as side effects.
     */
    private function dumpObject(
        object $value,
        DumpContextInterface $context,
        StatementBufferInterface $buffer
    ): string {
        // If already registered (handles circular references and shared object references),
        // return the existing variable name without re-dumping.
        if ($context->hasObject($value)) {
            return $context->getObjectVarName($value);
        }

        $varName = $context->registerObject($value);
        $class = $value::class;
        $reflection = new ReflectionObject($value);

        if ($value instanceof stdClass) {
            $buffer->addObjectCreation(sprintf('%s = new \\stdClass();', $varName));
        } elseif ($reflection->isInternal()) {
            // Internal PHP classes may not support newInstanceWithoutConstructor.
            $buffer->addObjectCreation(
                sprintf(
                    '%s = null; // Internal class %s cannot be safely instantiated.',
                    $varName,
                    $class
                )
            );

            return $varName;
        } else {
            $buffer->addObjectCreation(
                sprintf(
                    '%s = (new \\ReflectionClass(\\%s::class))->newInstanceWithoutConstructor();',
                    $varName,
                    $class
                )
            );
        }

        // Dump all instance properties, including private ones from parent classes.
        foreach ($reflection->getProperties() as $prop) {
            if ($prop->isStatic()) {
                continue;
            }

            if (!$prop->isInitialized($value)) {
                continue;
            }

            $propValue = $prop->getValue($value);
            $propName = $prop->getName();
            $declaringClass = $prop->getDeclaringClass()->getName();

            if ($propValue instanceof Closure) {
                $buffer->addObjectPropertySetup(
                    sprintf(
                        '// Property %s::$%s: closure, cannot be snapshotted.',
                        $declaringClass,
                        $propName
                    )
                );
                continue;
            }

            $dumpedValue = $this->dump($propValue, $context, $buffer);

            $buffer->addObjectPropertySetup(
                sprintf(
                    '(function ($obj, $v): void { $p = (new \\ReflectionClass(\\%s::class))->getProperty(%s); $p->setValue($obj, $v); })(%s, %s);',
                    $declaringClass,
                    var_export($propName, true),
                    $varName,
                    $dumpedValue
                )
            );
        }

        return $varName;
    }

    /**
     * Dumps a resource value to an inline PHP expression.
     *
     * Falls back to a null literal with a comment when the resource type is not supported.
     */
    private function dumpResource(mixed $value): string
    {
        $type = get_resource_type($value);

        return sprintf('/* unsupported resource type: %s */ null', $type);
    }
}
