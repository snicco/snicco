# Snicco Session: A custom session implementation for environments where `$_SESSION` can't be used

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://app.codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/Session/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

## Table of contents

1. [Motivation](#motivation)
2. [Installation](#installation)
3. [Usage](#usage)
    1. [Configuration](#creating-a-session-configuration)
    2. [Creating a serializer](#creating-a-serializer)
    3. [Drivers](#creating-a-session-driver)
    4. [Creating the session manager](#creating-a-session-manager)
    5. [Starting a session](#starting-a-session)
    6. [The immutable session](#the-immutable-session)
    7. [The mutable session](#the-mutable-session)
    8. [Accessing nested data](#accessing-nested-data)
    9. [Flash messages / Old input](#flash-messages--old-input)
    10. [Encrypting session data](#encrypting-session-data)
    11. [Saving a session](#saving-a-session)
    12. [Setting the session cookie](#setting-the-session-cookie)
    13. [Managing sessions based on user id](#managing-session-based-on-user-id)
4. [Contributing](#contributing)
5. [Issues and PR's](#reporting-issues-and-sending-pull-requests)
6. [Security](#security)

## Motivation

While **PHP's** native `$_SESSION` is fine for most use cases there are certain environments where it's not ideal. Two of
them being distributed **WordPress code** or **PSR7/PSR15** applications.

The **Session** component of the [**Snicco** project](https://github.com/sniccowp/sniccowp) is a **completely
standalone** library with zero dependencies on any framework.

Features:

- Automatically handles **invalidation**, **rotation** and **idle-timeouts**.
- Non-blocking.
- Tracks if sessions are dirty and only updates if needed (without affected timeouts).
- Only accepts server-side generated session IDs.
- Supports many storage backends. All in their separate composer packages.
- Uses
  [paragonie's split token approach](https://paragonie.com/blog/2017/02/split-tokens-token-based-authentication-protocols-without-side-channels)
  to protect against [timing based side-channel attacks](https://blog.ircmaxell.com/2014/11/its-all-about-time.html).

- Secure by design, it's not possible to hijack session ids by compromising the storage backend (assuming read-only access)
- PSR-7/15 compatible. No hidden dependencies on PHP super globals.
- Differentiation between **mutable** and **immutable** session objects.
- Choose between `json_encoding` your session data or `serializing` it. Or provide your own normalizer.
- Supports encrypting and decrypting session data (through an interface, don't panic).
- Advanced session management based on user ids.
- 100% test coverage and 100% **psalm** type-coverage.

## Installation

```sh
composer require sniccowp/session
```

## Usage

### Creating a session configuration

```php
use Snicco\Component\Session\ValueObject\SessionConfig;

$configuration = new SessionConfig([
    // The path were the session cookie will be available
    'path' => '/',
    // The session cookie name
    'cookie_name' => 'my_app_sessions',
    // This should practically never be set to false
    'http_only' => true,
    // This should practically never be set to false
    'secure' => true,
    // one of "Lax"|"Strict"|"None"
    'same_site' => 'Lax',
    // A session with inactivity greater than the idle_timeout will be regenerated and flushed
    'idle_timeout_in_sec' => 60 * 15,
    // Rotate session ids periodically
    'rotation_interval_in_sec' => 60 * 10,
    // Setting this value to NULL will make the session a "browser session".
    // Setting this to any positive integer will mean that the session will be regenerated and flushed
    // independently of activity.
    'absolute_lifetime_in_sec' => null,
    // The percentage that any given call to SessionManager::gc() will trigger garbage collection
    // of inactive sessions.
    'garbage_collection_percentage' => 2,
]);

```

---

### Creating a serializer

This package comes with two inbuilt serializers:

1. The [`JsonSerializer`](src/Serializer/JsonSerializer.php), which assumes that all your session content
   is `JsonSerializable`or equivalent.
2. The [`PHPSerializer`](src/Serializer/PHPSerializer.php), which will use `serialize` and `unserialize`.

If these don't work you, simply implement the  [`Serializer` interface](src/Serializer/Serializer.php).

---

### Creating a session driver

The [`SessionDriver`](src/Driver/SessionDriver.php) is an `interface` that abstracts away the concrete storage backend
for the session data.

Currently, the following drivers are available:

- [`InMemoryDriver`](src/Driver/InMemoryDriver.php), for usage during testing.
- [`EncryptedDriver`](src/Driver/EncryptedDriver.php), takes another `SessionDriver` as an argument and
  encrypts/decrypts its data.
- `Psr16Driver`, allows you to use any **PSR-16 cache**. You can use this driver by using
  the [`snicco/session-psr16-bridge`](https://github.com/sniccowp/session-psr16-bridge).

- `WPDBDriver`, you can use [`snicco/session-wp-bridge`](https://github.com/sniccowp/session-wp-bridge) to store sessions
    using the **WordPress** database.
- `WP_Object_Cache` you can use [`snicco/session-wp-bridge`](https://github.com/sniccowp/session-wp-bridge) to store sessions
  using the **WordPress** object cache.
- `Custom`, if none of these drivers works for you (and there is no **PSR-16** adapter) you can use [`snicco/session-testing`](https://github.com/sniccowp/session-testing)
    to test a custom implementation of yours against the interface.
---

### Creating a session manager

The [`SessionManager`](src/SessionManager/SessionManager.php) is responsible for creating and
persisting [`Session`](src/Session.php) objects.

```php
use Snicco\Component\Session\SessionManager\SessionManger;

$configuration = /* */
$serializer = /* */
$driver = /* */

$session_manger = new SessionManger($configuration, $driver, $serializer);
```

---

### Starting a session

The [`SessionManager`](src/SessionManager/SessionManager.php) uses an instance
of [`CookiePool`](src/ValueObject/CookiePool.php)
to start a session.

You can instantiate this object either from the `$_COOKIE` or any plain `array`.

Calling `SessionManger::start()` will handle:

1. Rejecting the session id and generating a new, empty session if the provided id can't be found in the driver.
2. Rotating the session id based on your configuration.
3. Rotate and clear the session if the session is idle based on your configuration.

```php
use Snicco\Component\Session\SessionManager\SessionManger;
use Snicco\Component\Session\ValueObject\CookiePool;

$configuration = /* */
$serializer = /* */
$driver = /* */

$session_manger = new SessionManger($configuration, $driver, $serializer);

// using $_COOKIE
$cookie_pool = CookiePool::fromSuperGlobals();

// or any array.
$cookie_pool = new CookiePool($psr7_request->getCookieParams());


$session = $session_manger->start($cookie_pool);
```

Calling `SessionManager::start()` will return an instance of [`Session`](src/Session.php).
[`Session`](src/Session.php) is an interface that extends both the [`MutableSession` interface](src/MutableSession.php)
and the [`ImmutableSession` interface](src/ImmutableSession.php).

This allows you to clearly separate the different concerns of reading and writing to the session.

In your code you should either depend on [`MutableSession`](src/MutableSession.php)
or [`ImmutableSession`](src/ImmutableSession.php).

The [`Session`](src/Session.php) interface is only needed to persist the session with the session manager.

---

### The immutable session

The [`ImmutableSession`](src/ImmutableSession.php) only has methods that return data. There is no way to modify the
session.

```php
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\ValueObject\ReadOnlySession;

/**
* @var Session $session 
*/
$session = $session_manger->start($cookie_pool);

// You can either rely on type-hints or transform $session to an immutable object like so:
$read_only_session = ReadOnlySession::fromSession($session);

function readFromSession(ImmutableSession $session) {
    
    $session->id(); // instance of SessionId
    
    $session->isNew(); // true/false
        
    $session->userId(); // int|string|null
        
    $session->createdAt(); // timestamp. Can never be changed.
        
    $session->lastRotation(); // timestamp
    
    $session->lastActivity(); // last activity is updated each time a session is saved.
    
    $session->has('foo'); // true/false
    
    $session->boolean('wants_beta_features'); // true/false
    
    $session->only(['foo', 'bar']); // only get keys "foo" and "bar"
    
    $session->get('foo', 'default'); // get key "foo" with optional default value
    
    $session->all(); // Returns array of all user provided data.
    
    $session->oldInput('username', ''); // Old input is flushed after saving a session twice.
    
    $session->hasOldInput('username'); // true/false
    
    $session->missing(['foo', 'bar']); // Returns true if all the given keys are not in the session.
    
    $session->missing(['foo', 'bar']); // Returns true if all the given keys are in the session.
    
}
```

---

### The mutable session

The [`Mutable`](src/MutableSession.php) only has methods that **modify** data. There is no way to read the session data.

```php
use Snicco\Component\Session\MutableSession;
use Snicco\Component\Session\Session;
use Snicco\Component\Session\ValueObject\ReadOnlySession;

/**
* @var Session $session 
*/
$session = $session_manger->start($cookie_pool);

function modifySession(MutableSession $session) {
    
    // Store the current user after authentication.
    $session->setUserId('user-1');
    
    // Rotates the session id and flushes all data.
    $session->invalidate();
        
    // Rotates the session id WITHOUT flushing data.
    $session->rotate(); 
        
    $session->put('foo', 'bar');
    $session->put(['foo' => 'bar', 'baz' => 'biz']);
        
    $session->putIfMissing('foo', 'bar');
    
    $session->increment('views');
    $session->increment('views', 2); // Increment by 2
    
    $session->decrement('views');
    $session->decrement('views', 2); // Decrement by 2
    
    $session->push('viewed_pages', 'foo-page'); // Push a value onto an array.
    
    $session->remove('foo');
    
    $session->flash('account_created', 'Your account was created'); // account_created is only available during the current request and the next request.
    
    $session->flashNow('account_created', 'Your account was created' ); // account_created is only available during the current request.
    
    $session->flashInput('login_form.email', 'calvin@snicco.io'); // This value is available during the current request and the next request.

    $session->reflash(); // Reflash all flash data for one more request.
    
    $session->keep(['account_created']); // Keep account created for one more request.
    
    $session->flush(); // Empty the session data.
    
}
```

--- 

### Accessing nested data

Nested data can be accessed using "dots".

```php
$session->put([
    'foo' => [
        'bar' => 'baz'
    ]   
]);

var_dump($session->get('foo.bar')); // baz
```

### Flash messages / Old input

Flashing data to the session means storing it only until the session is saved **twice**.

The most common use case for this is to display toast notifications after a `POST` request.

```php
// POST request: 

// create user account and redirect to success page.

$session->flash('account_created', 'Great! Your account was created.');

// session is saved.

// GET request:

echo $session->get('account_created');

// session is saved again, account_created is now gone.
```

Old input works very similar. The most common use case is to display submitted form data on failure to validate the
form.

```php
// POST request: 

$username = $_POST['username'];

// validate the request...

// Validation failed.
$session->flashInput('username', $username);

// session is saved.

// GET request:

if($session->hasOldInput('username')) {
    $username = $session->oldInput('username');
    // Use username to populate the form values again.
}

// session is saved again, username is now gone.
```
---

### Encrypting session data

If you are storing sensitive data in your session you can use the [`EncryptedDriver`](src/Driver/EncryptedDriver.php).

This driver will wrap another (inner) session driver and encrypt/decrypt your data before passing it to your application
code.

To function, the [`EncryptedDriver`](src/Driver/EncryptedDriver.php) needs an instance
of [`SessionEncryptor`](src/SessionEncryptor.php), which is a dead-simple interface with no implementation.

Here is how you would use [`defuse/php-encryption`](https://github.com/defuse/php-encryption) to encrypt your sessions.

```php
use Snicco\Component\Session\Driver\EncryptedDriver;
use Snicco\Component\Session\SessionEncryptor;

final class DefuseSessionEncryptor implements SessionEncryptor
{
    private string $key;

    public function __construct(string $key)
    {
        $this->$key = $key;
    }

    public function encrypt(string $data): string
    {
        return Defuse\Crypto\Crypto::encrypt($data, $this->key);
    }

    public function decrypt(string $data): string
    {
       return Defuse\Crypto\Crypto::decrypt($data, $this->key);
    }
}

$driver = new EncryptedDriver(
    $inner_driver,
    new DefuseSessionEncryptor($your_key)
)
```

### Saving a session

[`Session`](src/Session.php) is a **value object**. Changes in the session are only persisted when the
[session manager](#creating-a-sessionmanagersrcsessionmanagersessionmanagerphp) saves it.

Once a [`Session`](src/Session.php) is saved it is locked. Calling any state changing methods on a locked session will
throw a [`SessionIsLocked`](src/Exception/SessionIsLocked.php) exception.

Calling `save` on an unmodified session will only update the last activity of the session using `SessionDriver::touch()`
.

This eliminates a lot a race-conditions that might happen with overlapping GET/POST requests that read and write a
session.

```php
use Snicco\Component\Session\SessionManager\SessionManger;
use Snicco\Component\Session\ValueObject\CookiePool;

$configuration = /* */
$serializer = /* */
$driver = /* */
$cookie_pool = /* */;

$session_manger = new SessionManger($configuration, $driver, $serializer);

$session = $session_manger->start($cookie_pool);

$session->put('foo', 'bar');

$session_manger->save($session);

// This will throw an exception.
$session->put('foo', 'baz');
```

---

### Setting the session cookie

Setting cookies is out of scope for this library (because we don't know how you handle HTTP concerns in your application).

Instead, the session manager provides a method to retrieve a [`SessionCookie`](src/ValueObject/SessionCookie.php) value object
from a session.

An example on how to use the [`SessionCookie`](src/ValueObject/SessionCookie.php) class to set the session cookie
using `setcookie`. 
You can do something similar if you are using **PSR-7** requests.

```php

use Snicco\Component\Session\SessionManager\SessionManger;
use Snicco\Component\Session\ValueObject\CookiePool;

$configuration = /* */
$serializer = /* */
$driver = /* */
$cookie_pool = /* */;

$session_manger = new SessionManger($configuration, $driver, $serializer);

$session = $session_manger->start($cookie_pool);

$session->put('foo', 'bar');

$session_manger->save($session);

$cookie = $session_manger->toCookie($session);

$same_site = $cookie->sameSite();
$same_site = ('None; Secure' === $same_site) ? 'None' : $same_site;

setcookie($cookie->name(), $cookie->value(), [
    'expires' => $cookie->expiryTimestamp(),
    'samesite' => $same_site,
    'secure' => $cookie->secureOnly(),
    'path' => $cookie->path(),
    'httponly' => $cookie->httpOnly(),
]);
```

---

### Managing session based on user id

It's **not** a requirement to store user ids in your session.

However, if you choose so, this package provides some nice tools to manage sessions based on user ids.

The [`UserSessionsDriver`](src/Driver/UserSessionsDriver.php) extends the [`SessionDriver`](src/Driver/SessionDriver.php) interface.

Not all [drivers](#creating-a-sessiondriversrcdriversessiondriverphp) support this interface tho.

```php
use Snicco\Component\Session\Driver\InMemoryDriver;

// The in memory driver implements UserSessionDriver
$in_memory_driver = new InMemoryDriver();

// Destroy all sessions, for all users.
$in_memory_driver->destroyAllForAllUsers();

// Destroys all sessions where the user id has been set to (int) 12.
// Useful for "log me out everywhere" functionality.
$in_memory_driver->destroyAllForUserId(12);

$session_selector = $session->id()->selector();
// Destroys all sessions for user 12 expect the passed one.
// Useful for "log me out everywhere else" functionality.
$in_memory_driver->destroyAllForUserIdExcept($session_selector, 12);

// Returns an array of SerializedSessions for user 12.
$in_memory_driver->getAllForUserId(12);
```

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
