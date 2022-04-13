# Open redirect protection middleware for [`snicco/http-routing`](https://github.com/snicco/http-routing)

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/OpenRedirectProtection/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

This middleware protects your application against [open redirects](https://cheatsheetseries.owasp.org/cheatsheets/Unvalidated_Redirects_and_Forwards_Cheat_Sheet.html).

It inspects the `location` header of the response and disallows any redirects to non-whitelisted
external hosts.

Instead, the user will be redirected to the configured "exit" page.
The intended redirect location will be available in a `intented_redirect` query variable.

## Installation

```shell
composer require snicco/open-redirect-protection-middleware
```

## Usage

This middleware should be added globally in the `MiddlewareResolver`.

The [`OpenRedirectProtection`](src/OpenRedirectProtection.php) middleware must be bound in the **PSR-11** container
that is used by the [`snicco/http-routing`](https://github.com/snicco/http-routing) component.

````php
use Snicco\Middleware\OpenRedirectProtection\OpenRedirectProtection;

// In your PSR-11 container.
$open_redirect_protection = new OpenRedirectProtection(
    'snicco.io', // the host of your application
    '/exit', // the page path
    [
        'stripe.com',
        'accounts.stripe.com'    
    ] // Whitelisted domains.
)

````

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
