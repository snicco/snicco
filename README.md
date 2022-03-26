# SniccoWP - Taking the pain out of enterprise WordPress development

[![codecov](https://codecov.io/gh/sniccowp/sniccowp/branch/master/graph/badge.svg?token=4W8R6FZ948)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

In this
development [monorepo](https://tomasvotruba.com/blog/2019/10/28/all-you-always-wanted-to-know-about-monorepo-but-were-afraid-to-ask/)
you'll find many independent packages that will help you to develop testable, maintainable and PSR-compatible enterprise
**WordPress** projects faster than ever before.

All packages in this repo are specifically designed to be usable in distributed **WordPress** code and
are [scopable](https://github.com/humbug/php-scoper) out of the box.

## Table of contents

1. [Repository Overview](#repository-overview)
    1. [Component](#component)
    2. [Bridge](#bridge)
    3. [Middleware](#middleware)
    4. [Bundle](#bundle)
    5. [Testing](#testing)
2. [Contributing](#contributing)
3. [Changelog](#changelog)
4. [Security](#security)
5. [License](#license)
6. [Credits](#credits)

## Repository Overview

You can find all packages in the [`src/Snicco`](./src/Snicco) directory.

Here is a brief overview of the repository:
(hint: click the links to go to the dedicated documentation for each package)

### Component

This directory contains completely decoupled PHP components that you can use in any WordPress (or any other PHP project)
.

- [BetterWPAPI](https://github.com/sniccowp/better-wp-api): A better way to interacts with **WordPress** core functions
  in distributed **WordPress** packages.
- [BetterWPCache](https://github.com/sniccowp/better-wp-cache): A PSR6/PSR16 implementation using
  the [`WP_Object_Cache`](https://developer.wordpress.org/reference/classes/wp_object_cache/). Supports cache tagging.
- [BetterWPHooks](https://github.com/sniccowp/better-wp-hooks): The **WordPress** hook system redesign in 2022. (PSR-14
  compliant)
- [BetterWPMail](https://github.com/sniccowp/better-wp-mail): The long overdue upgrade to
  the [`wp_mail`](https://developer.wordpress.org/reference/functions/wp_mail/) function.
- [BetterWPDB](https://github.com/sniccowp/better-wpdb): Keeps you safe and sane when working with custom tables in
  **WordPress**.
- [EventDispatcher](https://github.com/sniccowp/event-dispatcher): A general purpose, PSR-14 compliant event-dispatcher
  that powers [`sniccowp/better-wp-hooks`](https://github.com/sniccowp/better-wp-hooks).
- [HttpRouting](https://github.com/sniccowp/http-routing): A blazing fast routing system and PSR7/PSR15 middleware
  dispatcher based on fast-route. Especially build for usage in legacy software where you don't have 100% control over
  the request-lifecycle.
- [Kernel](https://github.com/sniccowp/kernel): A minimal and customizable application bootstrapper.
- [Psr7ErrorHandler](https://github.com/sniccowp/psr7-error-handler): A powerful, PSR-7/PSR-3 compliant error-handler.
- [Session](https://github.com/sniccowp/session): A custom session implementation for applications where `$_SESSION`
  can't be used for various reasons.
- [SignedURL](https://github.com/sniccowp/signed-url): A standalone package to generate and validate protected urls.
- [StrArr](https://github.com/sniccowp/str-arr): A zero-dependency,type-safe and **psalm**-compatible implementation of
  the
  [illuminate/support](https://github.com/illuminate/support/blob/master/Str.php) string and array helpers.
- [Templating](https://github.com/sniccowp/templating): A unified, immutable API for using and combining different
  template engines.
- [TestableClock](https://github.com/sniccowp/testable-clock): Helper classes for testing time-dependent code.

### Bridge

This directory contains different implementations for interfaces defined in one of the components.

- [Blade](https://github.com/sniccowp/blade-bridge): Provides a 100% tested, standalone implementation
  of [illuminate/view](https://github.com/illuminate/view)
  for [`sniccowp/templating`](https://github.com/sniccowp/templating).
- [IlluminateContainer](https://github.com/sniccowp/illuminate-container-bridge): Provides an adapter
  for [illuminate/container](https://github.com/illuminate/container) for the usage
  with [`sniccowp/kernel`](https://github.com/sniccowp/kernel).
- [Pimple](https://github.com/sniccowp/pimple-bridge): Provides an adapter
  for [pimple/pimple](https://github.com/pimple/pimple) for the usage
  with[`sniccowp/kernel`](https://github.com/sniccowp/kernel).
- [Session-PSR16](https://github.com/sniccowp/session-psr16-bridge): Allows
  using [`sniccowp/session`](https://github.com/sniccowp/session) with any PSR16 cache backend.
- [SessionWP](https://github.com/sniccowp/session-wp-bridge): Allows
  using  [`sniccowp/session`](https://github.com/sniccowp/session) with a custom table or the
  [`WP_Object_Cache`](https://developer.wordpress.org/reference/classes/wp_object_cache/).
- [SingedURL-PSR15](https://github.com/sniccowp/signed-url-psr15-bridge): Provides a PSR15 middleware
  for [`sniccowp/signed-url`](https://github.com/sniccowp/signed-url).
- [SingedURL-PSR16](https://github.com/sniccowp/signed-url-psr16-bridge): Allows using any PSR16 cache as a storage
  for [`sniccowp/signed-url`](https://github.com/sniccowp/signed-url).

### Middleware

This directory contains PSR15-Middleware that can be plugged into the `sniccowp/http-routing` component.

- [DefaultHeaders](https://github.com/sniccowp/default-headers-middleware): Add custom headers to all outgoing
  responses.
- [HttpsOnly](https://github.com/sniccowp/https-only-middleware): Redirects HTTP => HTTPS for all requests
- [MethodOverride](https://github.com/sniccowp/method-override-middleware): Allows treating form submissions
  as `PUT|PATCH|DELETE` requests.
- [Negotiation](https://github.com/sniccowp/negotiation-middleware): Performs content and language negotiation
- [NoRobots](https://github.com/sniccowp/no-robots-middleware): Disallows search-engines to index the current request
  path.
- [Payload](https://github.com/sniccowp/payload-middleware): Transforms JSON (and other) data to plain PHP arrays.
- [Redirect](https://github.com/sniccowp/redirect-middleware): Redirects requests to configured locations.
- [ShareCookies](https://github.com/sniccowp/share-cookies-middleware): Transforms cookie objects into response headers.
- [TrailingSlash](https://github.com/sniccowp/trailing-slash-middleware): Redirects `/foo/` to `/foo` or vice-versa.
- [WPAuthOnly](https://github.com/sniccowp/wp-auth-only-middleware): Grants access only to authenticated **WordPress**
  users.
- [WPCapability](https://github.com/sniccowp/wp-cap-middleware): Grants access only to **WordPress** users with the
  configured capability.
- [WPGuestsOnly](https://github.com/sniccowp/wp-guests-only-middleware): Grants access only to guest **WordPress**
  users.
- [WPNonce](https://github.com/sniccowp/wp-nonce-middleware): Will solve all
  your [WordPress Nonces](https://codex.wordpress.org/WordPress_Nonces) problems once and for all.

### Bundle

A `bundle` is a plugin for the [`sniccowp/kernel`](https://github.com/sniccowp/kernel) component and integrates one or
more [components](#component)
or [bridges](#bridge) to provide out of the box functionality.

While all components can absolutely be used without using the [`sniccowp/kernel`](https://github.com/sniccowp/kernel)
component, bundles makes usage and configuration effortless.

- [BetterWPCacheBundle](https://github.com/sniccowp/better-wp-cache-bundle)
- [BetterWPHooksBundle](https://github.com/sniccowp/better-wp-hooks-bundle)
- [BetterWPMailBundle](https://github.com/sniccowp/better-wp-mail-bundle)
- [BetterWPDBBundle](https://github.com/sniccowp/better-wpdb-bundle)
- [BladeBundle](https://github.com/sniccowp/blade-bundle)
- [DebugBundle](https://github.com/sniccowp/debug-bundle): Integrates [filp/whoops](https://github.com/filp/whoops)
  with [`sniccowp/psr7-error-handler`](https://github.com/sniccowp/psr7-error-handler)
- [EncryptionBundle](https://github.com/sniccowp/encryption-bundle): A tight integration with
  the [defuse/php-encryption](https://github.com/defuse/php-encryption) library.
- [HttpRoutingBundle](https://github.com/sniccowp/http-routing-bundle)
- [SessionBundle](https://github.com/sniccowp/session-bundle):
  Integrates [`sniccowp/session`](https://github.com/sniccowp/session)
  , [`sniccowp/http-routing`](https://github.com/sniccowp/http-routing)
  and [`sniccowp/session-wp-bridge`](https://github.com/sniccowp/session-wp-bridge)
- [TemplatingBundle](https://github.com/sniccowp/templating-bundle)
- [TestingBundle](https://github.com/sniccowp/testing-bundle): A full-stack testing framework for applications build
  with [`sniccowp/kernel`](https://github.com/sniccowp/kernel) based
  on [codeception/codeception](https://github.com/Codeception/Codeception)
  and [lucatume/wp-browser](https://github.com/lucatume/wp-browser).

### Testing

This directory contains testing utilities for [components](#component). These packages are only meant to be used as
development dependencies.

- [BetterWPMailTesting](https://github.com/sniccowp/better-wp-mail-testing): Provides a testable mail transport for
  usage in tests.
- [EventDispatcherTesting](https://github.com/sniccowp/better-wp-mail-testing): Provides a testable event dispatcher for
  usage in tests.
- [HttpRoutingTesting](https://github.com/sniccowp/http-routing-testing): Provides utilities to test your custom
  Middleware.
- [KernelTesting](https://github.com/sniccowp/kernel-testing): Contains a test case to test custom dependency-injection
  adapters
- [SessionTesting](https://github.com/sniccowp/session-testing): Contains test cases to test custom session drivers
  for [`sniccowp/session`](https://github.com/sniccowp/session).
- [SignedUrlTesting](https://github.com/sniccowp/signed-url-testing): Contains test cases to test custom storage
  for [`sniccowp/signed-url`](https://github.com/sniccowp/signed-url).

## Contributing

We've set up a separate document for our [contribution guidelines](CONTRIBUTING.md).

## Changelog

[Our changelog](CHANGELOG.md) is automatically generated using the wonderful **npm**
package [semantic-release](https://github.com/semantic-release/semantic-release).

## Security

Please review our [security policy](SECURITY.md) on how to securely report vulnerabilities.

## License

This project is licensed under the terms of the GNU LGPLv3 unless otherwise specified in the respective LICENSE.md file
of each package. See [LICENSE.md](LICENSE.md)

## Credits

We want to express our special gratitude to:

- [@matthiasnoback](https://github.com/matthiasnoback): For his teachings
  on [PHP package design](https://matthiasnoback.nl/book/principles-of-package-design/). It had a lot of influence on
  how we set up this project.
- [@TomasVotruba](https://github.com/TomasVotruba): For his teachings
  on [managing a PHP monorepo](https://tomasvotruba.com/).

