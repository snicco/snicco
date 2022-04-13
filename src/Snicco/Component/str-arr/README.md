# Snicco - Zero dependency string and array helpers

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/StrArr/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This package contains a subset of the [`illuminate/support`](https://github.com/illuminate/support) `Str` and `Arr`
classes.

Laravel's string and array helpers are very handy utility classes
but [pulling in the entire `illuminate/support` is not an option](https://mattallan.me/posts/dont-use-illuminate-support)
when you are writing a framework-agnostic package.

The following modifications have been made:

`Str`:

- full multibyte support for all methods
- strict-typehints
- `Str` is a final class
- all hidden `illuminate/*` dependencies are removed
- full support for `@psalm`.

`Arr`:

- strict-typehints
- `Arr` is a final class
- all hidden `illuminate/*` dependencies are removed
- `Collection` references are replaces with `ArrayAccess` or `ArrayObject` where applicable
- full support for `@psalm` and `@template` annotations.

## Installation

```shell
composer require snicco/str-arr
```

## Usage

This package is pretty much self-documenting.

Check the source ([`Str`](src/Str.php), [`Arr`](src/Arr.php)) and tests ([`Str`](tests/StrTest.php)
, [`Arr`](tests/ArrTest.php))

Public API of [`Str`](src/Str.php):

```php
use Snicco\Component\StrArr\Str;

$subject = 'snicco.io';

Str::contains($subject, '.io') // true
Str::containsAll($subject, ['.io', '.com']) // false
Str::containsAny($subject, ['.io', '.com']) // true

Str::studly('snicco str-arr'); // Snicco StrArr

Str::ucfirst($subject); // Snicco.io

Str::startsWith($subject, 'snicco') // true

Str::endsWith($subject, '.io') // true
Str::doesNotEndWith($subject, '.io') // false

Str::afterFirst($subject, 'c') // co.io
Str::afterLast($subject, 'c') // o.io

Str::betweenFirst($subject, 'c', 'o') // o
Str::betweenLast($subject, 'c', 'o') // co.io

Str::beforeFirst($subject, 'o') // snicc
Str::beforeLast($subject, 'o') // snicco.i

Str::substr($subject, -3) // .io

// This accepts any regex pattern. * will be replaced with ".*"
Str::is($subject, 'snicco.*') // true

Str::replaceFirst($subject, 'c', 'k') // snikco.io
Str::replaceAll($subject, 'c', 'k') // snikko.io

Str::pregReplace($subject, 'c', '/\.\w{2}/', '.de') // snicco.de
```

Public API of [`Arr`](src/Arr.php):

```php
use Snicco\Component\StrArr\Arr;use Snicco\Component\StrArr\Str;

$array = [
    'foo' => 'bar'
    'baz' => 'biz'
    'boom' => [
        'bang' => 'pow'
    ]   
]

Arr::only($array, ['foo']) // ['foo'=>'bar']

// Returns the first array element
Arr::first($array) // bar

// Returns the first element matching the condition
Arr::first(
    $array, 
    fn(string $value, string $key) => Str::startsWith($key, 'f')
); // bar

// With a default value
Arr::first($array, fn($value) => is_int($value), 'default') // default

Arr::random($array, 1) // returns one random value.

Arr::toArray('foo') // ['foo']
Arr::toArray([]) // []
Arr::toArray(['foo']) // ['foo']

// Checks if all keys are strings
Arr::isAssoc($array) // true

Arr::isList($array) // false
Arr::isList(['foo', 'bar']) // true

Arr::get($array, 'foo') // bar
Arr::get($array, 'bogus') // null
Arr::get($array, 'bogus', 'default') // default
Arr::get($array, 'boom.bang') // pow

// passed by reference here
Arr::set($array, 'boom.bang', 'POW');

Arr::has($array, 'foo') // true
Arr::has($array, 'bogus') // false
Arr::has($array, 'boom.bang') // true

Arr::hasAll(['foo', 'bogus']) // false

Arr::hasAny(['foo', 'bogus']) // true

// Checks if the passed value is either array or ArrayAccess
Arr::accessible($array) // true

Arr::mergeRecursive($array, ['boom' => ['bang' => 'POW', 'new' => 'NEW']]) 
// [ 'foo' => 'bar'
//    'baz' => 'biz'
//    'boom' => [
//        'bang' => 'POW',
//        'new => 'NEW'
//    ]
// ]

Arr::keyExists($array, 'foo') // true

Arr::flatten($array) // ['bar', 'biz', 'pow']

Arr::except($array, ['foo', 'baz'])
// [
//    'boom' => [
//        'bang' => 'POW',
//        'new => 'NEW'
//    ]
// ]

// Passed by reference here
Arr::remove($array, 'boom.bang');
// [ 'foo' => 'bar'
//    'baz' => 'biz'
//    'boom' => []
// ]

```

## Contributing

This repository is a read-only split of the development repo of the
[**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
