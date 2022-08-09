# BetterWPHooks - A 2022 redesign of the WordPress hooks system (PSR-14-compatible)

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/snicco/snicco)
[![Psalm Type-Coverage](https://shepherd.dev/github/snicco/snicco/coverage.svg?)](https://shepherd.dev/github/snicco/snicco)
[![Psalm level](https://shepherd.dev/github/snicco/snicco/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://snicco.github.io/snicco/phpmetrics/BetterWPHooks/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

**BetterWPHooks** is a small library that allows you to write **modern**, **testable**  and **object-oriented** code in
complex **WordPress** projects.

## Table of contents

1. [Motivation](#motivation)
2. [Installation](#installation)
3. [Usage](#usage)
    1. [Event listeners](#event-listeners)
    2. [Dispatching events](#dispatching-events)
    3. [The Event interface](#the-event-interface)
    4. [Event subscribers](#event-subscribers)
    5. [Removing listeners](#removing-event-listeners)
    6. [Mapping events to core/third-party hooks](#mapping-core-and-third-party-actions)
       1. [Ensuring your event fires first](#ensuring-your-mapped-event-fires-first)
       2. [Ensuring your event fires last](#ensuring-your-mapped-event-fires-last)
    7. [Exposing (some of) your events to WordPress](#exposing-some-of-your-events-to-the-wordpress-hook-system)
    8. [A better alternative to apply_filters](#a-better-alternative-to-apply_filters)
    9. [Stopping event flow/propagation](#stopping-event-flowpropagation)
4. [Testing](#testing)
5. [Contributing](#contributing)
6. [Issues and PR's](#reporting-issues-and-sending-pull-requests)
7. [Security](#security)

## Motivation

**BetterWPHooks** is a central component in the
[**Snicco** project](https://github.com/snicco/snicco) and was developed because the [**WordPress hook** system](
)
suffers the following problems:

1. You have no [**type-safety**](https://psalm.dev/) at all when using [`add_action`](https://developer.wordpress.org/reference/functions/add_action/) and [`add_filter`](https://developer.wordpress.org/reference/functions/add_filter/). Anything can be
   returned.
2. An event (hook) should ideally be **immutable**, meaning that it can't be changed. Using [`apply_filters`]((https://developer.wordpress.org/reference/functions/apply_filters/)) the original
   arguments are immediately lost as soon as the first callback is run.
3. There is **no proper place to define hooks and callbacks**. Many developers default to putting hooks into
   the [class constructor](https://github.com/wp-plugins/woocommerce/blob/master/includes/class-wc-session-handler.php#L39)
   which is a bad solution for many reasons.
4. [Dependency injection](https://php-di.org/doc/understanding-di.html) is not supported. **You can't lazily instantiate class callbacks**.This leads to either massive
   pollution of the global namespace with custom functions, or instantiating all classes of a codebase on each and every
   request. Not quite performant.
5. There is no way to define which hooks are for public usage and which one are internal to your codebase.
6. It's [extremely difficult to remove hooks](https://inpsyde.com/en/remove-wordpress-hooks/) that are registered as
   closures or object methods.
7. It's very hard to test hooks without using additional test frameworks like [WP_Mock](https://github.com/10up/wp_mock)
   or [Brain Monkey](https://github.com/Brain-WP/BrainMonkey)
   . ([mocking sucks](https://twitter.com/icooper/status/1036527957798412288))

While throwing in a quick action here and there is completely fine for small projects,
for [enterprise level projects](https://github.com/snicco/snicco) or complex distributed plugins **WordPress hooks**
become a maintenance and testability burden.

## Installation

```shell
composer require snicco/better-wp-hooks
```

## Usage

### Creating an event dispatcher

```php
use Snicco\Component\BetterWPHooks\WPEventDispatcher;

$dispatcher = WPEventDispatcher::fromDefaults();
```

By default, your event listeners (**WordPress** calls them hook callbacks) are assumed to be newable classes (`$instance = new MyClass()`).

Optionally (**but strongly recommended**), you can resolve your listeners using any [**PSR-11** container](https://www.php-fig.org/psr/psr-11/).

```php
use Snicco\Component\BetterWPHooks\WPEventDispatcher;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\EventDispatcher\ListenerFactory\PsrListenerFactory;

$your_psr_container = /* */

$base_dispatcher = new BaseEventDispatcher(new PsrListenerFactory($your_psr_container));

$dispatcher = new WPEventDispatcher($base_dispatcher);
```

---

### Event listeners

These are the valid ways to attach listeners to any event:

```php
use Snicco\Component\BetterWPHooks\WPEventDispatcher;

$dispatcher = WPEventDispatcher::fromDefaults();

// Assumes OrderListener has an __invoke method
$dispatcher->listen(OrderCreated::class, OrderListener::class);

// String names work for events
$dispatcher->listen('order_created', OrderListener::class);

// Any public method works
$dispatcher->listen(OrderCreated::class, [OrderListener::class, 'someMethod']);

// A simple closure listener
$dispatcher->listen(OrderCreated::class, function(OrderCreated $event) {
    // 
});

// This is the same as above
$dispatcher->listen(function(OrderCreated $event) {
    // 
});

```

---

### Dispatching events

Any event is dispatched by using the `dispatch` method on your `WPEventDispatcher` instance.

The dispatch method accepts any `object`. By default, the class name of the event will be used to determine the
listeners that should be created and called.

Since **BetterWPHooks** is **PSR-14** compliant, every call to `dispatch` will return the same object instance that was
being passed.

```php
use Snicco\Component\BetterWPHooks\WPEventDispatcher;

$dispatcher = WPEventDispatcher::fromDefaults();
$dispatcher->listen(OrderCreated::class, function (OrderCreated $event) {
    // Do stuff with order
    $order = $event->order;
});

$order = /* */

$event = new OrderCreated($order);

// This will lazily create and call all listeners
// that are attached to OrderCreated::class event
$result = $dispatcher->dispatch($event);

var_dump($event === $result); // true
```

You can create generic events on the fly if for some reason you don't want to create a dedicated event class:
The first constructor argument of `GenericEvent` is the event name, the second one an array of arguments that will be
passed to all listeners.

```php
use Snicco\Component\BetterWPHooks\WPEventDispatcher;
use Snicco\Component\EventDispatcher\GenericEvent;

$dispatcher = WPEventDispatcher::fromDefaults();

$dispatcher->listen('order_created', function (Order $order) {
    // Do stuff with order
});

$order = /* */

$dispatcher->dispatch(new GenericEvent('order_created', [$order]));
```

--- 

### The `Event` interface

**BetterWPHooks** comes with an interface that you can use to fully customize the behaviour of your events.

```php
interface Event
{
    public function name(): string;
    
   /**
    * @return mixed  
    */
    public function payload();
}
```

Assuming the `OrderCreated` event implements this `interface`:

```php
class OrderCreated implements Event {
            
    private Order $order;
    
    public function __construct(Order $order) {
        $this->order = $order;
    }        
            
    public function name() :string {
        return 'order.created'
    }
    
    public function payload() : {
        return [$this, time()];
    }
}
```

Your code would now look like this:

```php
use Snicco\Component\BetterWPHooks\WPEventDispatcher;
use Snicco\Component\EventDispatcher\GenericEvent;

$dispatcher = WPEventDispatcher::fromDefaults();

$dispatcher->listen('order.created', function (Order $order, int $timestamp) {
    // Do stuff with order
});

$order = /* */

$dispatcher->dispatch(new OrderCreated($order));
```

--- 

### Event Subscribers

Instead of defining all your listeners using the `listen` method you can also implement the `EventSubscriber` interface
and use the `subscribe` method on the `WPEventDispatcher`.

```php
use Snicco\Component\BetterWPHooks\WPEventDispatcher;
use Snicco\Component\EventDispatcher\EventSubscriber;
use Snicco\Component\EventDispatcher\GenericEvent;

class OrderSubscriber implements EventSubscriber {
    
   public static function subscribedEvents() : array{
        
        return [
           OrderCreated::class => 'sendShippingNotification',
           OrderCanceled::class => 'sendCancelNotification'
        ];
   }
   
   public function sendShippingNotification(OrderCreated $event) :void {
        // 
   }
   
   public function sendCancelNotification(OrderCreated $event) :void {
        // 
   }
   
}

$dispatcher = WPEventDispatcher::fromDefaults();

$dispatcher->subscribe(OrderSubscriber::class);

$order = /* */

$dispatcher->dispatch(new OrderCreated($order));
$dispatcher->dispatch(new OrderCanceled($order));
```

---

### Removing event listeners

In most cases, your event dispatcher should be immutable after the bootstrapping phase of your application/plugin. If
however you want to remove events/listeners you can do it like so:

```php
use Snicco\Component\BetterWPHooks\WPEventDispatcher;

$dispatcher = WPEventDispatcher::fromDefaults();

// This will remove ALL listeners for the order created event.
$dispatcher->remove(OrderCreated::class);

// This will remove only one listener
$dispatcher->remove(OrderCreated::class, [OrderListener::class, 'someMethod']);
```

If you want to prevent the removal of a specific listener you can implement the `Unremovable` interface. If an
unremovable listener is being removed an `CantRemoveListener` exception will be thrown.

```php
use Snicco\Component\BetterWPHooks\WPEventDispatcher;
use Snicco\Component\EventDispatcher\Unremovable;

class OrderListener implements Unremovable {

    public function someMethod(OrderCreated $event){
        //
    }

}

$dispatcher = WPEventDispatcher::fromDefaults();

// This will throw an exception
$dispatcher->remove(OrderCreated::class, [OrderListener::class, 'someMethod']);
```

---

### Mapping core and third party actions.

**BetterWPHooks** comes with a very useful `EventMapper` class. The `EventMapper` allows you transform **WordPress**
core or other third-party actions/filters into proper event objects.

It serves as a thin layer in between your code and external hooks.

Mapped events MUST either implement `MappedHook` or `MappedFilter`

Implement `MappedHook` if you are mapping your event to and action, `MappedFilter` if you are mapping to a filter
that expects are return value.

Utilizing the `EventMapper`, you get to keep all the benefits of **BetterWPHooks** like lazy-loading your listeners
while still being able to interacts with third-party code the same way as before.

The `shouldDispatch` method on the `MappedHook` interface gives you great control over your event flow.
If `shouldDispatcher` returns `(bool) false` all attached listeners will not be called.

This allows you to build highly customized and performant integrations with third-party code.

**An example for mapping to an action:**

(This event will only be dispatched if the user performing the order is logged in)

```php
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\BetterWPHooks\WPEventDispatcher;

class LoggedInUserCreatedOrder implements MappedHook {
    
    public int $order_id;
    public int $current_user_id;
    
    public function __construct(int $order_id, int $current_user_id) {
    
       $this->order_id = $order_id;
       $this->current_user_id = $current_user_id;
       
    }
    
    public function shouldDispatch() : bool{
        return $this->current_user_id > 0;
    }
    
}

$wp_dispatcher = WPEventDispatcher::fromDefaults();

$wp_dispatcher->listen(function (LoggedInUserCreatedOrder $event) {
    $id = $event->order_id;
    $user_id = $event->current_user_id;
});

$event_mapper = new EventMapper($wp_dispatcher);
$event_mapper->map('woocommerce_order_created', LoggedInUserCreatedOrder::class, 10);

do_action('woocommerce_order_created', 1000, 1);
```

**An example for mapping to a filter:**

(This event will always be dispatched since we return `true`)

```php
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\BetterWPHooks\EventMapping\MappedFilter;
use Snicco\Component\BetterWPHooks\WPEventDispatcher;

class DeterminingOrderPrice implements MappedFilter {
    
    public int $new_total;
    public int $initial_order_total;
    
    public function __construct(int $initial_order_total) {
        $this->new_total = $intial_order_total;
        $this->initial_order_total = $intial_order_total;
    }
    
    public function filterableAttribute(){
        return $this->new_total;
    }
    
    public function shouldDispatch() : bool{
        return true;
    }
    
}

$wp_dispatcher = WPEventDispatcher::fromDefaults();

$wp_dispatcher->listen(function (DeterminingOrderPrice $event) {
   if($some_condition) {
        $event->new_total+= 5000;
   }
});

$wp_dispatcher->listen(function (DeterminingOrderPrice $event) {
   if($some_condition) {
        $event->new_total+= 4000;
   }
});

$event_mapper = new EventMapper($wp_dispatcher);
$event_mapper->map('woocommerce_order_total', DeterminingOrderPrice::class, 10);

// Somewhere in woocommerce
$order_total = apply_filters('woocommerce_order_total', 1000);

var_dump($order_total); // (int) 10000
```

#### Ensuring your mapped event fires first 

Using the `mapFirst` method on the `EventMapper` your event listeners will **always** be run before any other 
hook callbacks registered with **WordPress**.

```php
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\BetterWPHooks\WPEventDispatcher;

$wp_dispatcher = WPEventDispatcher::fromDefaults();

$wp_dispatcher->listen(OrderCreated::class, OrderListener::class);

$event_mapper = new EventMapper($wp_dispatcher);
$event_mapper->mapFirst('woocommerce_order_created', OrderCreated::class);

function some_other_random_callback() {

}
add_action('woocommerce_order_created', 'some_other_random_callback', PHP_INT_MIN);

// OrderListener will still be called first. 
do_action('woocommerce_order_created', 1000, 1);
```

#### Ensuring your mapped event fires last

Using the `mapLast` method on the `EventMapper` your event listeners will **always** be run after any other
hook callbacks registered with **WordPress**. This is especially useful for filters where you want to control the final
result.

```php
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\BetterWPHooks\WPEventDispatcher;

$wp_dispatcher = WPEventDispatcher::fromDefaults();

$wp_dispatcher->listen(OrderCreated::class, OrderListener::class);

$event_mapper = new EventMapper($wp_dispatcher);
$event_mapper->mapLast('woocommerce_order_created', OrderCreated::class);

function some_other_random_callback() {
    return 5000;
}
add_filter('woocommerce_order_created', 'some_other_random_callback', PHP_INT_MAX);

// OrderListener will still be called last. 
$order_total = apply_filters('woocommerce_order_total', 1000);
```

### Exposing (some of) your events to the WordPress hook system

The **WordPress** hook system is globally available. This is a problem. Both your code as a developer and for users
who want to interact with the custom events created by your application/plugin.

There is no way to enforce which events are safe to rely upon and which events might disappear tomorrow because you 
refactored your code.

The `ExposeToWP` interface helps with this.

By default, every time you dispatch an event **your internal listeners will be called first**.

If the dispatched event object implements the `ExposeToWP` interface the event object will be passed
to the WordPress hook system so that third-party developers can interact with your code within the scope that you define.

If the dispatched event object does not implement `ExposeToWP` it will **not** be available to **WordPress** hooks.

An example:

```php
use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;

class PrivateEvent {
    
}

class PublicEvent implements ExposeToWP {

}

add_action(PrivateEvent::class, function (PrivateEvent $event) {
    // This will never be called
});

add_action(PublicEvent::class, function (PublicEvent $event) {
     // This will be called.
});

$dispatcher->dispatch(new PrivateEvent());

$dispatcher->dispatch(new PublicEvent());

```

### A better alternative to `apply_filters`

The [**PSR-14** meta documentation](https://www.php-fig.org/psr/psr-14/meta/) defines four common goals of an event system:

- One-way notification. ("I did a thing, if you care.")
- Object enhancement. ("Here's a thing, please modify it before I do something with it.")
- Collection. ("Give me all your things, that I may do something with that list.")
- Alternative chain. ("Here's a thing; the first one of you that can handle it do so, then stop.")

Most of the time using [`apply_filters`](https://developer.wordpress.org/reference/functions/apply_filters/) in your code means that you want to enhance behaviour or allow other developers
to customize the behaviour of your code. (**Object enhancement**)

[`apply_filters`](https://developer.wordpress.org/reference/functions/apply_filters/) is not ideal for this as its return type is `mixed`.
There is nothing stopping a third-party developer mistakenly returning `(int) 0` when you are expecting `(bool) false`.

Event objects allow you to enforce [type-safety](https://psalm.dev) so that you don't have to manually type-check the 
end-result of every filter.

**Here is what we recommend and use in our code:**

```php
use Snicco\Component\BetterWPHooks\EventMapping\ExposeToWP;

class PerformingUserDeletion implements ExposeToWP {

    public bool $is_allowed = true;
    private int $user_being_deleted;
    private int $current_user_id;
    
    public function __construct(int $user_being_deleted, int $current_user_id) {
         $this->user_being_deleted = $user_being_deleted;
         $this->current_user_id = $current_user_id;
    }
    
    public function userBeingDeleted(): int{
        return $this->user_being_deleted;
    }
    
    public function currentUserId(): int{
        return $this->current_user_id;
    }
    
}

// Some third-party-code:
add_filter(PerformingUserDeletion::class, function(PerformingUserDeletion $event) {
    
    // The user with id 10 must never be deleted.
    if(10 === $event->userBeingDeleted()) {
        $event->is_allowed = false;
    }
    
});

// Your code.
$action = $dispatcher->dispatch(new PerformingUserDeletion(10, 1));

// There is no way that this is not a boolean.
if(!$action->is_allowed) {
    throw new Exception('You have no permission to delete this user.');
}

// Delete user.
```

---

### Stopping event flow/propagation

In some cases, it may make sense for a listener to prevent any other listeners from being called. In other words, the listener needs to be able to tell the dispatcher to stop all propagation of the event to future listeners (i.e. to not notify any more listeners).

In order for this to work your event object must implement the [PSR-14 StoppableEventInterface](https://github.com/php-fig/event-dispatcher/blob/1.0.0/src/StoppableEventInterface.php).

An example:

```php
use Psr\EventDispatcher\StoppableEventInterface;

class DeterminingOrderPrice implements StoppableEventInterface {
    
    public int $initial_price;
    public int $order_total;
    
    public function __construct( int $initial_price ) {
        $this->order_total = $initial_price;
        $this->initial_price = $initial_price;
    }
    
    public function isPropagationStopped() : bool{
        return $this->order_total >= 2000    
    }
    
    
}

$dispatcher->listen(function (DeterminingOrderPrice $event) {
    $event->order_total+=200
})

$dispatcher->listen(function (DeterminingOrderPrice $event) {
    $event->order_total+=800
})

$dispatcher->listen(function (DeterminingOrderPrice $event) {
   throw new Exception('This will never be called.');
})

$dispatcher->dispatch(new DeterminingOrderPrice(1000));

```

## Testing

**BetterWPHooks** comes with dedicated testing utilities for **phpunit**.

First, install:


```shell
composer require snicco/event-dispatcher-testing --dev
```

This package should be installed as `dev dependency` with composer. **It's not intended for production use**.

Now, in your tests, you should wrap your configured `WPEventDispatcher` with the `TestableEventDispatcher`. 
How you do that depends on how you structured your codebase.

The `TestableEventDispachter` wraps the `WPEventDispatcher` and can make assertions about the dispatched events in your tests.

Furthermore, you can fake events so that they will not be passed to the real `WPEventDispatcher`.

The `dispatch`, `listen`, `subscribe`, `remove` methods will be proxied to the `WPEventDispatcher`.
The following assertions methods are available.

```php
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;

$testable_dispatcher = new TestableEventDispatcher(WPEventDispatcher::fromDefaults());

$testable_dispatcher->assertNotingDispatched();

$testable_dispatcher->assertNotDispatched(OrderCreated::class);

$testable_dispatcher->assertDispatched(OrderCreated::class);

$testable_dispatcher->assertDispatchedTimes(OrderCreated::class, 2);

// With conditions.

$testable_dispatcher->assertDispatched(function (OrderCreated $event) {
    return $event->order->total >= 1000;
});

$testable_dispatcher->assertNotDispatched(function (OrderCreated $event) {
    return $event->order->total >= 1000;
});
```

Certain events can be faked like this:

```php
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;

$testable_dispatcher = new TestableEventDispatcher(WPEventDispatcher::fromDefaults());

// No event will be passed to the real dispatcher, assertions still work.
$testable_dispatcher->fakeAll();

// Fake one (or more) events. They will be not passed to the real dispatcher
// while all other events will.
$testable_dispatcher->fake(OrderCreated::class);
$testable_dispatcher->fake([OrderCreated::class, OrderDeleted::class]);

$testable_dispatcher->fakeExcept(OrderCreated::class);

$testable_dispatcher->resetDispatchedEvents();
```

## Contributing

This repository is a read-only split of the development repo of the [**Snicco** project](https://github.com/snicco/snicco).

[This is how you can contribute](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/snicco/snicco/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPHooks**, please follow
our [disclosure procedure](https://github.com/snicco/snicco/blob/master/SECURITY.md).
