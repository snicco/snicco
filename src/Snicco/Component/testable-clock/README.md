# TestableClock - PHP7.4 compatible clock abstraction

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/TestableClock/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

The **TestableClock** component of the [**Snicco** project](https://github.com/snicco/snicco) decouples code from `DateTimeImmutable` so that its properly testable.

## Installation

```shell
composer require snicco/testable-clock
```

## Usage

Instead of using `DateTimeImmutable` and `time()` directly in your code you use the [`Clock` interface](src/Clock.php).

```php
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;
use Snicco\Component\TestableClock\TestClock;

class IsTrialExpired {
    
    private  Clock $clock;
    
    public function __construct(Clock $clock) {
        $this->clock = $clock;
    }
        
    public function __invoke(object $trial) {
        return $trial->expiresAt > $this->clock->currentTimestamp();
    }
        
}

// In production code
$clock = SystemClock::fromUTC();

// or
$clock = new SystemClock(new DateTimeZone('Europe/Berlin'));

$is_trial_expired = new IsTrialExpired($clock)

// In test code
$test_clock = new TestClock();

$is_trial_expired = new IsTrialExpired($test_clock)
```

The [`TestClock`](src/TestClock.php) stays frozen, meaning it will always return the same timestamp.

You can advance the clock, or go back in time.

```php
use Snicco\Component\TestableClock\TestClock;

$clock = new TestClock();

$time_0 = $clock->currentTimestamp();

var_dump(time() === $time_0); // true

sleep(10);

$time_01 = $clock->currentTimestamp();

var_dump($time_01 === $time_0); // true
var_dump(time() === $time_01); // false

$clock->travelIntoFuture(10);

$time_02 = $clock->currentTimestamp();

var_dump(time() === $time_02); // true

$time_03 = $clock->travelIntoPast(10);

var_dump($time_03 === $time_01); // true 
```


## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
