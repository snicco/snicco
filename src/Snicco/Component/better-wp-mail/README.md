# BetterWPMail - The long overdue upgrade to `wp_mail`

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/BetterWPMail/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

**BetterWPMail** is a small library that provides an expressive, object-orientated API around the [`wp_mail`](https://developer.wordpress.org/reference/functions/wp_mail/) function.

**BetterWPMail** is not an SMTP-plugin!

It has (optional) support for many mail-transports, but will default to using a `WPMailTransport` so that it's usable in
distributed **WordPress** code.

## Table of contents

1. [Motivation](#motivation)
2. [Installation](#installation)
3. [Usage](#usage)
4. [Testing](#testing)
5. [Contributing](#contributing)
6. [Issues and PR's](#reporting-issues-and-sending-pull-requests)
7. [Security](#security)

## Motivation

To list all problems of the `wp_mail` function would take a long time. The most problematic ones are:

- ❌ No support for a plain-text version when sending a html body.
- ❌ No support for inline-attachments.
- ❌ No support for complex multi-part emails.
- ❌ Can't choose a custom filename for attachments.
- ❌ Can't send attachments that you already have in memory (like a generated PDF). You always have to write to a tmp file first.
- ❌ Zero error-handling.
- ❌ No support for templated emails.
- ...
- ...

## Installation

```shell
composer require snicco/better-wp-hooks
```

## Usage

## Testing

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/sniccowp/sniccowp).

[This is how you can contribute](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPHooks**, please follow
our [disclosure procedure](https://github.com/sniccowp/sniccowp/blob/master/SECURITY.md).
