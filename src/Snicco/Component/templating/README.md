# Snicco-Templating: A uniform API around various PHP template engines.

[![codecov](https://img.shields.io/badge/Coverage-100%25-success)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/Templating/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

The **Templating** component of the [**Snicco** project](https://github.com/sniccowp/sniccowp) provides a simple,
object-oriented API around popular **PHP** template engines.

## Table of contents

1. [Installation](#installation)
2. [Usage](#usage)
    1. [Overview](#usage)
    2. [Creating a view](#creating-a-view)
    3. [Directly render a view](#directly-rendering-a-view)
    4. [Finding the first existing view](#finding-the-first-existing-view)
    5. [Referencing nested directories](#referencing-nested-directories)
    6. [Global context / View composers](#global-context--view-composers)
    7. [View factories](#using-view-factories)
    8. [The PHPViewFactory](#the-phpviewfactory)
       1. [Instantiation](#instantiation)
       2. [Template inheritance](#template-inheritance)
3. [Contributing](#contributing)
4. [Issues and PR's](#reporting-issues-and-sending-pull-requests)
5. [Security](#security)

## Installation

```shell
composer require snicco/templating
```

## Usage

This package consists of the following main components:

- An **immutable** [`View`](src/ValueObject/View.php) object, which can be rendered to its string representation with a
  given `context`.
- The [`TemplateEngine`](src/TemplateEngine.php), which is a facade class used to create and
  render  [`View`](src/ValueObject/View.php) objects.
- The [`ViewFactory` interface](src/ViewFactory/ViewFactory.php), which abstracts away implementation details of how
  a `View` instance is rendered. See [Using view factories](#view-factories);
- The [`ViewContextResolver`](src/Context/ViewContextResolver.php), which is responsible for adding global `context` and
  view composer `context`
  to a view that is being rendered.

The following directory structure is assumed for the examples in this README:

```
your-project-root
├── templates/
│   ├── users/                 
│   │   ├── profile.php  
│   │   └── ...
│   └── hello-world.php             
└── ...
```

```php
// ./templates/hello-world.php

echo "Hello $first_name";
```

---

### Creating a view

An instance of [`View`](src/ValueObject/View.php) is always created by a call to `TemplateEngine::make()`.

Context can be added to a [`View`](src/ValueObject/View.php) instance which will be available to the underlying template
once the [`View`](src/ValueObject/View.php) is rendered.

```php
use Snicco\Component\Templating\TemplateEngine;

// The TemplateEngine accepts one or more instances of ViewFactory.
// See #Using view factories for available implementations.
$view_factory = /* */ 

$engine = new TemplateEngine($view_factory);

// hello-world is relative to the root directory "templates"
$view = $engine->make('hello-world');

$view1 = $view->with('first_name', 'Calvin');
$output = $engine->renderView($view1);
var_dump($output); // Hello Calvin

$view2 = $view->with('first_name', 'Marlon');
$output = $engine->renderView($view2);
var_dump($output); // Hello Marlon

// Views can also be created by passing an absolute path
$view = $engine->make('/path/to/templates/hello-world.php');
```

---

### Directly rendering a view

If you want to render a template right away you can use the `render` method on
the [TemplateEngine](src/TemplateEngine.php).

```php
use Snicco\Component\Templating\TemplateEngine;

$view_factory = /* */ 

$engine = new TemplateEngine($view_factory);

$output = $engine->render('hello-world', ['first_name' => 'Calvin']);
var_dump($output); // Hello Calvin
```

---

### Finding the first existing view

Both the `make` and `render` method of the [TemplateEngine](src/TemplateEngine.php) accept an `array` of strings in
order to use the first existing view.

```php
use Snicco\Component\Templating\TemplateEngine;

$view_factory = /* */ 

$engine = new TemplateEngine($view_factory);

$view = $engine->make(['hello-world-custom', 'hello-world']);

$output = $engine->render(['hello-world-custom', 'hello-world'], ['first_name' => 'Calvin']);
var_dump($output); // Hello Calvin
```

If no view can be found, a [`ViewNotFound`](src/Exception/ViewNotFound.php) exception will be thrown.

---

### Referencing nested directories

Both the `make` and `render` method of the [TemplateEngine](src/TemplateEngine.php) will expand dots to allow directory
traversal. This works independently of the concrete [ViewFactory](#usage) that is being used.

```php
use Snicco\Component\Templating\TemplateEngine;

$view_factory = /* */ 

$engine = new TemplateEngine($view_factory);

$view = $engine->make('users.profile');

$output = $engine->render('users.profile', ['first_name' => 'Calvin']);
```

---

### Global context / View composers

Before a view is rendered, it's passed to the [`ViewContextResolver`](src/Context/ViewContextResolver.php), which is
responsible for applying:

1. global `context` that should be available in all views
2. `context` provided by view composers to **some views**

A view composer can be a `Closure` or class that implements [`ViewComposer`](src/Context/ViewComposer.php).

The [`ViewContextResolver`](src/Context/ViewContextResolver.php) will be needed to instantiate the concrete
implementations of the view factory interface.

**Adding global context:**

```php
use Snicco\Component\Templating\Context\ViewContextResolver;
use Snicco\Component\Templating\Context\GlobalViewContext;

$global_context = new GlobalViewContext()

// All templates now have access to a variable called $site_name
$global_context->add('site_name', 'snicco.io');

// The value can be a closure which will be called lazily.
$global_context->add('some_var', fn() => 'some_value');

$context_resolver = new ViewContextResolver($global_context);
```

If you pass an `array` as the second argument to `GlobalViewContext::add` you can reference nested values in your views
like so:

```php
use Snicco\Component\Templating\Context\ViewContextResolver;
use Snicco\Component\Templating\Context\GlobalViewContext;

$global_context = new GlobalViewContext()
$global_context->add('app', [
   'request' => [
       'path' => '/foo',
       'query_string' => 'bar=baz'
   ]
]);

// Inside any template
echo $app['request.path']
echo $app['request.query_string']
```

**Adding view composers:**

```php
$context_resolver = /* */

// Using a closure
$context_resolver->addComposer('hello-world', fn(View $view) => $view->with('foo', 'bar'));

// Using a class that implements ViewComposer
$context_resolver->addComposer('hello-world', HelloWorldComposer::class);

// Adding a composer to multiple views
$context_resolver->addComposer(['hello-world', 'other-view'], fn(View $view) => $view->with('foo', 'bar'));

// Adding a composer by wildcard
// This will make the current user available to all views inside the templates/users directory.
$context_resolver->addComposer('users.*', fn(View $view) => $view->with('current_user', Auth::user()));
```

---

### Using view factories

All view factories implement the [`ViewFactory`](src/ViewFactory/ViewFactory.php) interface.

They are used by the `TemplateEngine` and contain the underlying logic to render a `View` instance to its string
representation.

It's possible to use multiple view factories together in which case the first factory that can render a given `View`
will be used.

The following view factories are currently available:

- [`PHPViewFactory`](src/ViewFactory/PHPViewFactory.php), included in this package. A bare-bones implementation that
  works great for small projects with only a handful of views.
- `BladeViewFactory`, included in a [separate package](https://github.com/sniccowp/blade-bridge). Integrates
  Laraval's **Blade** as a standalone template engine with this package, while retaining all features of both.
- `TwigViewFactory` - coming soon.

---

### The `PHPViewFactory`

The [`PHPViewFactory`](src/ViewFactory/PHPViewFactory.php) is a bare-bones implementation that is great for small
projects where you might only have a handful of views.

#### Instantiation

The [`PHPViewFactory`](src/ViewFactory/PHPViewFactory.php) takes
a [`ViewContextResolver`](#global-context--view-composers)
as the first argument and an `array` of **root** template directories as the second argument.

If a view exists in more than one template directory, the first matching one will be used. This is great for allowing
certain templates to be overwritten by templates in another (custom) template directory.

```php
use Snicco\Component\Templating\TemplateEngine;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;

$context_resolver = /* */

$php_view_factory = new PHPViewFactory(
    $context_resolver, 
    [__DIR__.'/templates']
);

$template_engine = new TemplateEngine($php_view_factory);
```

#### Template inheritance

The [`PHPViewFactory`](src/ViewFactory/PHPViewFactory.php) allows for very basic template inheritance.

**Assuming that we have the following two templates:**

```php
<?php
// post.php

/*
 * Extends: post-layout
 */

echo "Post one content"

```

```php
<?php
// post-layout.php
?>
<html lang="en">

    <head>
        Head content goes here
        <title><?= htmlentities($title, ENT_QUOTES, 'UTF-8') ?></title>
    </head>
    <body>
        <main>
            <?= $__content ?>
        </main>
        
        <footer>
            Footer content goes here
        </footer>
    </body>
</html>
```

**Rendering the post view will yield:**

```php
$template_engine->render('post', ['title' => 'Post 1 Title']);
```

```html

<html lang="en">

<head>
    Head content goes here
    <title>Post 1 Title</title>
</head>
<body>
<main>
    Post one content
</main>

<footer>
    Footer content goes here
</footer>
</body>

</html>
```

A couple of things to note:

- The parent template is indicated by putting `Extends: parent-view-name` inside `/* */` comments within the
  **first 100 bytes** of the template.
- `Context` that is passed to a child view is available to the parent view.
- The parent view can output the child-content by using the `$__content` variable.
- Nested inheritance is possible. `post-layout.php` could for example extend `layout.php`

## Contributing

This repository is a read-only split of the development repo of the
[**Snicco** project](https://github.com/sniccowp/sniccowp).

[This is how you can contribute](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/sniccowp/sniccowp/blob/master/SECURITY.md).
