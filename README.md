# Nytris Intercooler

[![Build Status](https://github.com/nytris/intercooler/workflows/CI/badge.svg)](https://github.com/nytris/intercooler/actions?query=workflow%3ACI)

A PHP heap snapshotting library that accelerates application bootstrapping by caching the PHP heap state to a file.

## Overview

Intercooler takes a snapshot of the current PHP heap after your application has fully bootstrapped - capturing class static properties, global variables etc. - and serialises that state to a PHP file. On subsequent requests, including this file early in bootstrap instantly restores the heap, avoiding the overhead of container compilation, dependency injection setup, and other initialisation work on every request.

The application then handles each request as if running in a long-lived process, without actually sharing state between requests in an unsafe way.

## Installation

```bash
$ composer require nytris/intercooler
```

## Usage

### 1. Install the package

`nytris.config.php`
```php
<?php

use Nytris\Boot\BootConfig;
use Nytris\Intercooler\IntercoolerPackage;

$bootConfig = new BootConfig(...);
$bootConfig->installPackage(new IntercoolerPackage());

return $bootConfig;
```

### 2. Take a snapshot after bootstrap

After your application has fully bootstrapped and served a response, take a snapshot:

```php
<?php

use Nytris\Intercooler\Intercooler;

Intercooler::snapshot('/path/to/cache/heap-snapshot.php');
```

### 3. Restore the snapshot on subsequent requests

Early in your bootstrap - before the expensive initialisation work - restore the snapshot:

```php
<?php

use Nytris\Intercooler\Intercooler;

Intercooler::restore('/path/to/cache/heap-snapshot.php');
```

If the snapshot file exists, it will be required and the heap state restored. Your application can then proceed as if it had already bootstrapped.

## Configuration

`IntercoolerPackage` accepts the following options:

```php
new IntercoolerPackage(
    additionalTypeHandlers: [],  // Custom TypeHandlerInterface[] tried before built-in handlers
    excludedClasses: [],         // FQCNs of classes to exclude from snapshotting
    includeGlobals: true,        // Whether to snapshot global variables
)
```

### Excluding classes

When running tests, you will typically want to exclude test framework classes from the snapshot:

```php
new IntercoolerPackage(
    excludedClasses: array_diff(get_declared_classes(), [MyApp\TestHarness::class])
)
```

### Custom type handlers

To handle value types not supported out of the box (e.g. custom resource types), implement `TypeHandlerInterface`:

```php
<?php

declare(strict_types=1);

use Nytris\Intercooler\Type\Handler\TypeHandlerInterface;

class MyResourceHandler implements TypeHandlerInterface
{
    public function canHandle(mixed $value): bool
    {
        return is_resource($value) && get_resource_type($value) === 'my-type';
    }

    public function dump(
        mixed $value,
        DumpContextInterface $context,
        StatementBufferInterface $buffer,
        ValueDumperInterface $valueDumper
    ): string {
        // Return a PHP expression that reconstructs the value.
        return 'my_resource_factory()';
    }
}

new IntercoolerPackage(
    additionalTypeHandlers: [new MyResourceHandler()]
)
```

## What is snapshotted

The following heap state is captured:

- **Class static properties** - all user-defined classes, excluding internal PHP classes and any configured exclusions
- **Global variables** - all non-superglobal entries in `$GLOBALS` (when `includeGlobals` is `true`)

### Supported value types

| Type | Restoration |
|---|---|
| `null`, `bool`, `int`, `float` | Inline literal |
| `string` | `var_export()` |
| `array` | Recursive inline expression |
| Backed enum | `ClassName::from(value)` |
| Unit enum | `ClassName::CASE_NAME` |
| `stdClass` | `new \stdClass()` |
| User-defined object | `ReflectionClass::newInstanceWithoutConstructor()` + property setup |
| Stream resource | `fopen(path, mode)` + `fseek()` to restore position |
| Closure | Replaced with `null` (with comment) |
| Unsupported resource | Replaced with `null` (with comment) |

Circular references and shared object identity are preserved across the snapshot.

## Generated snapshot format

The generated PHP file contains four ordered sections:

```php
<?php
/*
 * Nytris Intercooler heap snapshot.
 * Generated at: 2026-02-24 12:34:56.
 * Do not edit manually.
 */

declare(strict_types=1);

// Object/resource creation.
$__ic_0 = (new \ReflectionClass(\SomeClass::class))->newInstanceWithoutConstructor();
$__ic_1 = fopen('/path/to/file', 'r');
fseek($__ic_1, 42);

// Object instance property setup.
(function ($obj, $v): void {
    $p = (new \ReflectionClass(\SomeClass::class))->getProperty('prop');
    $p->setValue($obj, $v);
})($__ic_0, 'value');

// Static property setup.
(function ($v): void {
    $p = (new \ReflectionClass(\SomeClass::class))->getProperty('staticProp');
    $p->setValue(null, $v);
})('staticValue');

// Global variable setup.
$GLOBALS['myVar'] = $__ic_0;
```

## License

MIT - see [MIT-LICENSE.txt](MIT-LICENSE.txt).
