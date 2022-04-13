# Snicco-Kernel: A bootstrapper for applications with a plugin architecture

[![codecov](https://img.shields.io/badge/Coverage-100%25-success)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/Kernel/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

The **Kernel** component of the [**Snicco** project](https://github.com/snicco/snicco) helps to bootstrap an
application that uses a plugin architecture.

## Table of contents

1. [Installation](#installation)
2. [Definitions](#definitions)
    1. [Kernel](#kernel)
    2. [Bootstrappers](#bootstrappers)
    3. [Bundles](#bundles)
    4. [Environment](#environment)
    5. [Directories](#directories)
    6. [Configuration files](#configuration-files)
3. [Usage](#usage)
    1. [Creating kernel](#creating-a-kernel)
    2. [Booting the kernel](#booting-a-kernel)
    3. [Lifecycle hooks](#)
    4. [Using the booted kernel](#using-the-booted-kernel)
4. [Contributing](#contributing)
5. [Issues and PR's](#reporting-issues-and-sending-pull-requests)
6. [Security](#security)

## Installation

```shell
composer require snicco/kernel
```

## Definitions

### Kernel

The [`Kernel`](src/Kernel.php) class helps to load and cache configuration files, define services in a
dependency-injection container and bootstrap an application in a controlled manner using any number
of [bootstrappers](#bootstrappers) and [bundles](#bundles).

---

### Bootstrappers

A bootstrapper can be any class that implements the [`Bootstrapper` interface](src/Bootstrapper.php):

A bootstrapper is a class responsible for "bootstrapping" one cohesive part of an application.

"Bootstrapping" could mean, for example: registering definitions in a dependency injection container or creating event
listeners.

Bootstrappers are the central place to configure the application.

```php
interface Bootstrapper
{
    public function shouldRun(Environment $env): bool;

    public function configure(WritableConfig $config, Kernel $kernel): void;

    public function register(Kernel $kernel): void;

    public function bootstrap(Kernel $kernel): void;
}
```

---

### Bundles

A bundle can be any class that implements the [`Bundle` interface](src/Bundle.php). The [`Bundle`](src/Bundle.php)
interface extends the [`Bootstrapper`](src/Bootstrapper.php) interface.

```php
interface Bundle extends Bootstrapper
{
    public function alias(): string;
}
```

The difference between a bundle and a bootstrapper is that a bundle is meant to be publicly distributed, while a
bootstrapper is internal to a specific application.

Bundles are aware of other bundles that are used by the same [`Kernel`](src/Kernel.php) instance.

---

### Environment

A [`Kernel`](src/Kernel.php) always needs an environment to run in.

The following environments are possible:

- production
- staging
- development
- testing
- debug (in combination with any non production env.)

```php
use Snicco\Component\Kernel\ValueObject\Environment;

$environment = Environment::prod()
$environment = Environment::testing()
$environment = Environment::staging()
$environment = Environment::dev()
$environment = Environment::fromString(getenv('APP_ENV'));
```

---

### Directories

A [`Kernel`](src/Kernel.php) always needs a [`Directories`](src/ValueObject/Directories.php) **value object** that
defines the location of:

- the base directory of the application.
- the config directory of the application.
- the log directory of the application.
- the cache directory of the application.

```php
use Snicco\Component\Kernel\ValueObject\Directories;

$directories = new Directories(
    __DIR__, // base directory,
    __DIR__ .'/config', // config directory
    __DIR__. '/var/cache', // cache directory
    __DIR__ . '/var/log' // log directory
)

// This is equivalent to the above:
$directories = Directories::fromDefaults(__DIR__);
```

---

### Dependency injection container

The [`Kernel`](src/Kernel.php) needs an instance of [`DIContainer`](src/DIContainer.php), which is an abstract class
that extends the
**PSR-11** container interface.

There are currently two implementations of this interface:

- [`snicco/pimple-bridge`](https://github.com/snicco/pimple-bridge),
  using [`pimple/pimple`](https://github.com/silexphp/Pimple)
- [`snicco/illuminate-container-bridge`](https://github.com/snicco/illuminate-container-bridge),
  using [`illuminate/container`](https://github.com/illuminate/container)

You can also provide your own implementation and test it using the test cases
in [`snicco/kernel-testing`](https://github.com/snicco/kernel-testing).

The [`DIContainer`](src/DIContainer.php) class is an abstraction meant to be used inside [bundles](#bundles).

Since bundles are distributed packages, they can't rely on a specific dependency-injection container. However, the
**PSR-11** container interface only defines how to fetch services from the container, not how to define them, which is
why the [`DIContainer`](src/DIContainer.php) abstraction is used.

--- 

### Configuration files

Every `.php` file inside the [config directory](#directories) will be used to create
a [`Config`](src/Configuration/Config.php) instance once the kernel is booted.

The following configuration inside your config directory:

```php
// config/routing.php

return [

    'route_directories' => [
        /* */    
    ]       
        
    'features' => [
        'feature-a' => true,
    ]       
]

```

would be loaded into the config instance like so:

```php
$config->get('routing');

$config->get('routing.route_directories');

$config->get('routing.features.feature-a');
```

The `kernel.php` configuration file is reserved since this is where [bundles](#bundles)
and [bootstrappers](#bootstrappers)
are defined:

```php
// config/kernel.php

use Snicco\Component\Kernel\ValueObject\Environment;

return [

    'bundles' => [
        
        // These bundles will be used in all environments
        Environment::ALL => [
            RoutingBundle::class
        ],
        // These bundles will only be used if the kernel environment is dev.
        Environment::DEV => [
            ProfilingBundle::class
        ],      
        
    ],
    
    // Bootstrappers are always used in all environments.
    'bootstrappers' => [
        BootstrapperA::class
    ]   

]


```

## Usage

### Creating a kernel

```php
use Snicco\Component\Kernel\Kernel;

$container = /* */
$env = /* */
$directories = /* */

$kernel = new Kernel(
    $container,
    $directories,
    $env
);

```

--- 

### Booting a kernel

There is a difference in what happens when the kernel is booted based on the current environment and whether the
configuration is already cached.

```php
$kernel = /* */

$kernel->boot();
```

#### Booting an "uncached" kernel:

1. All configuration files inside the [config directory](#directories) will be loaded from disk to create an instance
   of [`WritableConfig`](src/Configuration/WritableConfig.php).
2. The bundles and bootstrappers are read from the [`kernel.php` configuration file](#configuration-files).
3. The `shouldRun()` method is called for all bundles.
4. The `shouldRun()` method is called for all bootstrappers.
5. The `configure()` method is called for all bundles.
6. The `configure()` method is called for all bootstrappers.
7. The [`WritableConfig`](src/Configuration/WritableConfig.php) is combined into one file and written to the cache
   directory (if the current environment is production/staging).
8. The `register()` method is called for all bundles.
9. The `register()` method is called for all bootstrappers.
10. The `boot()` method is called for all bundles that are defined in
    the [`kernel.php` configuration file](#configuration-files).
11. The `boot()` method is called for all bootstrappers that are defined in
    the [`kernel.php` configuration file](#configuration-files).
12. The [`DIContainer`](src/DIContainer.php) is locked and no further modifications can be made.

#### Booting a "cached" kernel:

1. The cached configuration file is loaded from disk and a [`ReadOnlyConfig`](src/Configuration/ReadOnlyConfig.php)
   is created.
2. The bundles and bootstrappers are read from the [`ReadOnlyConfig`](src/Configuration/ReadOnlyConfig.php).
3. The `shouldRun()` method is called for all bundles.
4. The `shouldRun()` method is called for all bootstrappers.
5. The `register()` method is called for all bundles.
6. The `register()` method is called for all bootstrappers.
7. The `boot()` method is called for all bundles.
8. The `boot()` method is called for all bootstrappers.
9. The [`DIContainer`](src/DIContainer.php) is locked and no further modifications can be made.

--- 

- The `configure()` method should be used to extend the loaded configuration with default values and to validate the
  configuration for the specific bundle. **The `configure()` method is only called if the configuration is not cached
  yet**.

- The `register()` method should **only** be used to bind service definitions into
  the [`DIContainer`](src/DIContainer.php).

- The `boot()` method should be used to fetch services from the [`DIContainer`](src/DIContainer.php) and to configure
  them (if necessary). The container is already locked at this point and further modifications of service definitions
  are not possible. Attempting to modify the container from inside the `boot()` method of a bundle or bootstrapper will
  throw a `ContainerIsLocked` exception.

Each of these methods is always called first on all bundles, then on all bootstrappers.

This allows bootstrappers to customize behaviour of bundles (if desired).

---

### Lifecycle hooks

There are two extension points in the booting process of the kernel.

- After the configuration was loaded from disk (**only if the configuration is not cached already**). This is the last
  opportunity to modify the configuration before its cached.
- After all bundles and bootstrappers have been registered, but before they are booted. This is the last opportunity to
  change service definitions before the container is locked.

```php
use Snicco\Component\Kernel\Configuration\WritableConfig;use Snicco\Component\Kernel\Kernel;

$kernel = /* */

$kernel->afterConfigurationLoaded(function (WritableConfig $config) {
    if( $some_condition ) {
        $config->set('routing.features.feature-a', true);    
    }
});

$kernel->afterRegister(function (Kernel $kernel) {
    if($some_condition) {
        $kernel->container()->instance(LoggerInterface::class, new TestLogger());
    }
});

$kernel->boot();
```

---

### Using the booted kernel

After the container is booted, services provided by all bundles can be safely fetched.

An example:

```php
use Nyholm\Psr7Server\ServerRequestCreator;

$kernel->boot();

$server_request_creator = $kernel->container()->make(ServerRequestCreator::class);
$http_kernel = $kernel->container()->make(HttpKernel::class);

$response = $http_kernel->handle($server_request_creator->fromGlobals());
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
