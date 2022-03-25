# BetterWPMail - The long overdue upgrade to `wp_mail`

[![codecov](https://img.shields.io/badge/Coverage-100%25-success)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)
[![PhpMetrics - Static Analysis](https://img.shields.io/badge/PhpMetrics-Static_Analysis-2ea44f)](https://sniccowp.github.io/sniccowp/phpmetrics/BetterWPMail/index.html)
![PHP-Versions](https://img.shields.io/badge/PHP-%5E7.4%7C%5E8.0%7C%5E8.1-blue)

**BetterWPMail** is a small library that provides an expressive, object-orientated API around
the [`wp_mail`](https://developer.wordpress.org/reference/functions/wp_mail/) function.

**BetterWPMail** is not an SMTP-plugin!

It has (optional) support for many mail-transports, but will default to using a `WPMailTransport` so that it's usable in
distributed **WordPress** code.

## Table of contents

1. [Motivation](#motivation)
2. [Installation](#installation)
3. [Creating a mailer](#creating-a-mailer)
4. [Creating and sending emails](#creating-and-sending-emails)
   1. [Immutability](#immutability)
   2. [Sending an email](#sending-an-email)
   3. [Adding addresses](#adding-addresses)
   4. [Setting mail content](#setting-mail-content)
   5. [Adding context to templates](#adding-context-to-templates)
   6. [Adding attachments](#adding-attachments)
   7. [Embedding images](#embedding-images)
   8. [Adding custom headers](#adding-custom-headers)
   9. [Configuring emails globally](#configuring-emails-globally)
   10. [Extending the email class](#extending-the-email-class)
   11. [Using mail events](#using-mail-events)
   12. [Writing emails in markdown / Using a custom renderer](#writing-emails-in-markdown--using-a-custom-mailrenderer)
   13. [Handling exceptions](#handling-exceptions)
5. [Testing](#testing)
6. [Contributing](#contributing)
7. [Issues and PR's](#reporting-issues-and-sending-pull-requests)
8. [Security](#security)

## Motivation

To list all problems of the `wp_mail` function would take a long time. The most problematic ones are:

- ❌ No support for a plain-text version when sending a html body.
- ❌ No support for inline-attachments.
- ❌ No support for complex multi-part emails.
- ❌ Can't choose a custom filename for attachments.
- ❌ Can't send attachments that you already have in memory (like a generated PDF). You always have to write to a tmp
  file first.
- ❌ Zero error-handling.
- ❌ No support for templated emails.
- ...
- ...

Many plugins employ massive hacks to circumvent these issues:

Typical **WordPress** plugin code:

```php

function my_plugin_send_mail(string $to, string $html_message) {
    
    add_filter('phpmailer_init', 'add_plain_text');
    
    /* Add ten other filters */
    wp_mail($to, $html_message);
    
    remove_filter('phpmailer_init', 'add_plain_text')
    
    /* Remove ten other filters */
}

function add_plain_text(\PHPMailer\PHPMailer\PHPMailer $mailer) {
    $mailer->AltBody = strip_tags($mailer->Body);
}
```

**Why is this so bad?**

Besides, that fact that you are running a ton of unneeded code for every email you sent, what happens if `wp_mail`
throws an exception it recovered somewhere else?

You now have 10 callbacks added to every email that you send with `wp_mail` in generell. Not only `my_plugin_send_mail`.
Depending on what kind of filters you added, there is a great potential for bugs that are impossible to debug.

A real example of this can be seen here in
the [WooCommerce](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/includes/emails/class-wc-email.php#L647)
code base.
(Not bashing WooCommerce here, there is no alternative way with the way `wp_mail` currently works.)

Now, under the hood **WordPress** uses the bundled [PHPMailer](https://github.com/PHPMailer/PHPMailer) which is a
reputable and rock-solid library.
[PHPMailer](https://github.com/PHPMailer/PHPMailer) has native support for most of the problems listed above, `wp_mail`
just doesn't use them.

Here is where **BetterWPMail** comes into play.

## Installation

```shell
composer require snicco/better-wp-mail
```

## Creating a `Mailer`

Instead of using `wp_mail` directly, you'll use the `Mailer` class which is able to send `Email` objects.

Quickstart:

```php
use Snicco\Component\BetterWPMail\Mailer;

$mailer = new Mailer();
```

The full signature of `Mailer::__construct` is

```php
  public function __construct(
        ?Transport $transport = null,
        ?MailRenderer $mail_renderer = null,
        ?MailEvents $event_dispatcher = null,
        ?MailDefaults $default_config = null
    )
```

- `Transport` is an interface and will default to the `WPMailTransport` where all emails will eventually send
  using `wp_mail`.

  If you are using **BetterWPMail** in a controlled environment, you can provide your own implementation of
  the `Transport` interface.

  In the future we will create a [`symfony/mailer`](https://symfony.com/doc/current/mailer.html) transport which will
  allow you to send emails with any of dozens of providers that Symfony`s mailer integrates with.

- The `MailRender` interface is responsible for converting mail templates to html/plain-text content. By default,
  a `FileSystemRenderer` will be used, which searches for a file matching the template name.
- The `MailEvents` interface is responsible for firing events right before and right after an email was sent. By
  default, an instance of `NullEvents` will be used which will not emit any events.
- `MailDefaults` is responsible for providing fallback configuration for settings sender name, reply-to address etc.

## Creating and sending emails

### Immutability

The `Email` class is an **immutable** value object. You can not change an email once its created. All public methods on
the `Email` class return a **new, modified version** of the object.

Immutability is not common in the PHP community, but it's actually simple to understand:

```php
use Snicco\Component\BetterWPMail\ValueObject\Email;

$email = new Email();

❌ // This is incorrect.
$email->addTo('calvin@snicco.io');

✅ // This is correct
$email = $email->addTo('calvin@snicco.io');
```

The basic convention in **BetterWPMail** is:

- methods starting with **add** will merge attributes and return a **new** object.
- methods starting with **with** will replace attributes and return a **new** object.

```php
use Snicco\Component\BetterWPMail\ValueObject\Email;

$email = new Email();

$email = $email->addTo('calvin@snicco.io');
// The email has one recipient now.

$email = $email->addTo('marlon@snicco.io');
// The email has two recipients now.

$email = $email->withTo('jondoe@snicco.io');
// The email has one recipient "jondoe@snicco.io"
```

--- 

### Sending an email

Emails are sent using the [`Mailer`](#creating-a-mailer) class.

At the minimum, an email needs one recipient and a body (html/text/attachments):

```php
use Snicco\Component\BetterWPMail\ValueObject\Email;

$email = (new Email())->addTo('calvin@snicco.io')
                      ->withHtmlBody('<h1>BetterWPMail is awesome</h1>');

$mailer->send($email);
```

### Adding addresses

All the methods that require email addresses (from(), to(), etc.) accept `strings`, `arrays`, a `WP_User` instance or
a `MailBox` instance

```php
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\BetterWPMail\ValueObject\Mailbox;

$email = new Email();

$admin = new WP_User(1);

$email = $email
    
    // email address is a simple string
    ->addTo('calvin@snicco.io')
    
    // with an explicit display name
    ->addCc('Marlon <marlon@snicco.io>')
    
    // as an array, where the first argument is the email
    ->addBcc(['Jon Doe', 'jon@snicco.io'])        
    
    // as an array with a "name" + "email" key        
    ->addFrom(['name' => 'Jane Doe', 'email' => 'jane@snicco.io'])

    // with an instance of WP_USER
    ->addFrom($admin)        
    
    // with an instance of MailBox
    ->addReplyTo(Mailbox::create('no-reply@snicco.io'));
```

---

### Setting mail content

You have two options for settings the content of an email:

1. By setting it explicitly as a string
2. By setting a template on the email object which will be rendered before sending.

```php
use Snicco\Component\BetterWPMail\ValueObject\Email;

$email = (new Email())->addTo('calvin@snicco.io');

$email = $email
    ->withHtmlBody('<h1>BetterWPMail is awesome</h1>')
    ->withTextBody('BetterWPMail supports plain text.')

$templated_email = $email
    ->withHtmlTemplate('/path/to/template-html.php')
    ->withTextBody('/path/to/template-plain.txt')
```

If an email has html-content but **no** explicit text-content, then the html-content will be passed
through [`strip_tags`](https://www.php.net/manual/de/function.strip-tags.php) and be used as the plain-text version.

---

### Adding context to templates

Assuming that we want to send a welcome email to multiple users with the following template:

```php
<?php
// path/to/email-templates/welcome.php
?>
<h1>Hi <?= esc_html($first_name) ?>,

<p>Thanks for signing up to <?= esc_html($site_name) ?></p>
```

Here we can use the fact that emails are `immutable` to reuse a base email instance:

```php

use Snicco\Component\BetterWPMail\ValueObject\Email;

$email = (new Email())

    ->withHtmlTemplate('path/to/email-templates/welcome.php')
    
    ->withContext(['site_name' => 'snicco.io']);

// Important: don't use withContext here or site_name is gone.
$email1 = $email->addContext('first_name', 'Calvin')
                ->addTo('calvin@snicco.io');
                
$mailer->send($email1);

$email2 = $email->addContext('first_name', 'Marlon');
                ->addTo('marlon@snicco.io');
                
$mailer->send($email2);
```

This will result in the following two emails being sent:

```html

<h1>Hi Calvin,

    <p>Thanks for signing up to snicco.io</p>
```

```html

<h1>Hi Marlon,

    <p>Thanks for signing up to snicco.io</p>
```

--- 

### Adding attachments

Attachments can be added to an instance of `Email` in two ways:

1. Attaching a local path on the filesystem.

  ```php
  use Snicco\Component\BetterWPMail\ValueObject\Email;
  
  $email = (new Email())->addTo('calvin@snicco.io');
  
  $email = $email
  
      ->addAttachment('/path/to/documents/terms-of-use.pdf')
      
      // optionally with a custom display name
      ->addAttachment('/path/to/documents/privacy.pdf', 'Privacy Policy')
      
      // optionally with a custom content-type, will default to application/octet-stream.
      ->addAttachment('/path/to/documents/contract.doc', 'Contract', 'application/msword');
  
  ```

2. Attaching a binary string or a stream that you already have in memory (a generated PDF for example)

  ```php
  use Snicco\Component\BetterWPMail\ValueObject\Email;
  
  $pdf = /* generate pdf */
  
  $email = (new Email())->addTo('calvin@snicco.io');
  
  $email = $email
  
      ->addBinaryAttachment($pdf, 'Your PDF', 'application/pdf')
  
  ```

---

### Embedding Images

If you want to display images inside your email, you must embed them instead of adding them as attachments.

In your email content you can then reference the embedded image with the syntax:
`cid: + image embed name`

```php
<?php
// path/to/email-templates/welcome-with-image.php
?>
<h1>Hi <?= esc_html($first_name) ?>,

<img src="cid:logo">
```

```php
  use Snicco\Component\BetterWPMail\ValueObject\Email;
  
  $email = (new Email())
    ->addTo('calvin@snicco.io')
    ->addContext('first_name', 'Calvin');
  
  $email1 = $email
      ->addEmbed('/path/to/images/logo.png', 'logo', 'image/png')
      ->withHtmlTemplate('path/to/email-templates/welcome-with-image.php');
  
  // or with inline html
  $email2 = $email
      ->addEmbed('/path/to/images/logo.png', 'logo', 'image/png')
      ->withHtmlBody('<img src="cid:logo">');  

  ```

---

### Adding custom headers

```php
  use Snicco\Component\BetterWPMail\ValueObject\Email;
  
  $email = (new Email())
    ->addTo('calvin@snicco.io')
    // custom headers are string, string key value pairs.
    // These are not validated in any form.
    ->addCustomHeaders(['X-Auto-Response-Suppress'=> 'OOF, DR, RN, NRN, AutoReply'])
 ```

---

### Configuring emails globally

The default configuration for all your emails is determined by the `MailDefaults` class that you pass into the
`Mailer` class.

If you don't explicitly pass an instance of `MailDefaults` when creating your `Mailer`, they will be created based on
the global **WordPress** settings.

Remember: You can always overwrite these settings on a per-email basis.

```php
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\ValueObject\MailDefaults;

$from_name = 'My Plugin';
$from_email = 'myplugin@site.com';

$reply_to_name = 'My Plugin Reply-To'
$reply_to_email = 'myplugin-reply-to@site.com';

$mail_defaults = new MailDefaults(
    $from_name,
     $from_email, 
     $reply_to_name, 
     $reply_to_email
);

// Other arguments set to default for brevity.
$mailer = new Mailer(null, null, null, $mail_defaults);
```

---

### Extending the `Email` class

If you are sending the same email in multiple places, you might want to extend the `Email` class to preconfigure shared
settings in one place.

Creating your custom emails classes has a lot of synergy with [mail events](#using-mail-events).

An example for a custom welcome email:

```php
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\BetterWPMail\ValueObject\Mailbox;

class WelcomeEmail extends Email {
        
    // You can configure the protected
    // priorities of the Email class    
    protected ?int $priority = 5;
    
    protected string $text = 'We would like to welcome you to snicco.io';
    
    public function __construct(WP_User $user) {
    
        // configure dynamic properties in the constructor.
        $this->subject = sprintf('Welcome to snicco.io %s', $user->display_name);
        
        $this->to[] = Mailbox::create($user);
        
        $this->html_template = '/path/to/templates/welcome.php';
        
        $this->context['first_name'] = $user->first_name;
        
    }

}

$user = new WP_User(1);

$mailer->send(new WelcomeEmail($user));
```

---

### Using mail events

When you call `Mailer::send` two types of events are fired.

Right before passing the `Email` instance to the [`Transport`](#creating-a-mailer) interface the `SendingEmail` event is
fired. This event contains the current `Email` as a public property which gives you an opportunity to change its
settings before sending.

Right after an email is sent the `EmailWasSent` event is fired. This event is mainly useful for logging purposes.

To use mail events you have to pass in an instance of `MailEvents`
when [creating your mailer instance](#creating-a-mailer).

By default, **BetterWPMail** comes with an implementation of this interface that uses the 
[**WordPress** hook system](https://developer.wordpress.org/plugins/hooks/).

```php
use Snicco\Component\BetterWPMail\Event\MailEventsUsingWPHooks;
use Snicco\Component\BetterWPMail\Event\SendingEmail;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Tests\fixtures\Email\WelcomeEmail;
use Snicco\Component\BetterWPMail\Transport\WPMailTransport;

$mailer = new Mailer(
    null,
    null,
    new MailEventsUsingWPHooks()
);

add_filter(Email::class, function (SendingEmail $event) {
    // This will add 'admin@site.com' to every email that is being sent.
    $event->email = $event->email->addBcc('admin@site.com');
});

add_filter(WelcomeEmail::class, function (SendingEmail $event) {
    // This will add 'welcome@site.com' to every welcome email that is sent.
    $event->email = $event->email->addBcc('welcome@site.com');
});

```

A common use-case of mail events is allowing users to customize specific mails:

```php
// In your code
$user = new WP_User(1);
$mailer->send(new MyPluginWelcomeMail($user));

// Third-party code:
add_filter(MyPluginWelcomeMail::class, function (SendingEmail $event) {
    // This will overwrite your default template for the "MyPluginWelcomeEmail" only
    $event->email = $event->email->withHtmlTemplate('path/to/custom/welcome.php');
});

```

### Writing emails in markdown / Using a custom `MailRenderer`

If you pass no arguments when [creating your mailer instance](#creating-a-mailer) the default renderer will be used
which is a combination of:

- The `AggregateRenderer` (which delegates the rendering to between multiple `MailRenderer` instances)
- The `FilesystemRenderer` (which looks for a file that matches the template name set on the  `Email`)

Let's now create a custom setup:

- We want to render markdown emails and
- Use the `FilesystemRenderer` as a fallback.

First we need a way to convert markdown to HTML.

We will use [`erusev/parsedown`](https://github.com/erusev/parsedown) for this task.

```shell 
composer require erusev/parsedown
```

Now let's create a custom `MarkdownMailRenderer`:

```php
use Snicco\Component\BetterWPMail\Renderer\MailRenderer;

class MarkdownEmailRenderer implements MailRenderer {
    
    // This renderer should only render .md files that exist.
    public function supports(string $template_name,?string $extension = null) : bool{
        
        return 'md' === $extension && is_file($template_name);
            
    }
    
    public function render(string $template_name,array $context = []) : string{
        
        // First, we get the string contents of the template.
        $contents = file_get_contents($template_name);
        
        // To allow basic templating, replace placeholders inside {{ }} 
        foreach ($context as $name => $value ) {
            $contents = str_replace('{{'.$name'.}}', $value);
        }
        
        // Convert the markdown to HTML and return it.
        return (new Parsedown())->text($contents);
                        
    }
    
}

```

Now that we are ready to render markdown emails we can create our `Mailer` like this:

```php
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Renderer\AggregateRenderer;
use Snicco\Component\BetterWPMail\Renderer\FilesystemRenderer;
use Snicco\Component\BetterWPMail\ValueObject\Email;

// This mail renderer will use our new markdown renderer (if possible) and default the filesystem renderer.
$mail_renderer = new AggregateRenderer(
    new MarkdownMailRenderer(),
    new FilesystemRenderer(),
);

$mailer = new Mailer(null, $mail_renderer);

$email = new Email();
$email = $email->addTo('calvin@snicco.io');

// This email will be renderer with the default renderer
$email_html = $email->withHtmlTemplate('/path/to/templates/welcome.php');
$mailer->send($email_html);

// This email will be renderer with our new markdown renderer.
$email_markdown= $email->withHtmlTemplate('/path/to/templates/markdown/welcome.md');
$mailer->send($email_markdown);
```

---

### Handling exceptions

In contrast to `wp_mail`, calling `Mailer::send()` will throw a `CantSendEmail` exception on failure.

```php
use Snicco\Component\BetterWPMail\Exception\CantSendEmail;use Snicco\Component\BetterWPMail\ValueObject\Email;

$email = (new Email())->addTo('calvin@snicco.io')
                      ->withHtmlBody('<h1>BetterWPMail has awesome error handling</h1>');

try {
    $mailer->send($email);
} catch (CantSendEmail $e) {

   // You can catch this exception if you like,
   // or let it bubble up depending on your use case.
    error_log($e->getDebugData());
}
```

This has numerous advantages over the native way of interacting with `wp_mail`:

```php

function handleMailError(WP_Error $error) {
    // what now?
}

add_action('wp_mail_failed', 'handleMailError');

$success = wp_mail('calvin@snicco.io', 'wp_mail has bad error_handling');

remove_action('wp_mail_failed', 'handleMailError');

if($success === false) {
    // what now?
}

```

## Testing

**BetterWPMail** comes with a dedicated testing package that provides a `FakeTransport` class that you should
use during testing.

First, install the package as a composer `dev-dependency`:

```shell
composer install --dev snicco/better-wp-mail-testing
```

How you wire the `FakeTransport` into your `Mailer` instance during testing greatly depends on how your overall
codebase is set up. You probably want to do this inside your dependency-injection container.

The `FakeTranport` has the following **phpunit** assertion methods:

```php
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\ValueObject\Email;

$mailer = new Mailer($transport = new FakeTransport());

// This fill pass
$transport->assertNotSent(WelcomeEmail::class);

$mailer->send(new MyPluginWelcomeEmail());

// This will fail now.
$transport->assertNotSent(MyPluginWelcomeEmail::class);

// This will pass
$transport->assertSent(MyPluginWelcomeEmail::class);

// This will fail
$transport->assertSent(PurchaseEmail::class);

// This will fail
$transport->assertSentTimes(MyPluginWelcomeEmail:class, 2);

$mailer->send(new MyPluginWelcomeEmail());

// This will now pass.
$transport->assertSentTimes(MyPluginWelcomeEmail:class, 2);

$email = (new Email())->addTo('calvin@snicco.io');
$mailer->send($email);

// This will pass
$transport->assertSentTo('calvin@snicco.io');
// This will pass
$transport->assertNotSentTo('marlon@snicco.io');

$email = (new Email())->addTo('marlon@snicco.io');
$mailer->send($email);

// This will now fail.
$transport->assertNotSentTo('marlon@snicco.io');

// Using an assertion closure. This will pass.
$transport->assertSent(Email::class, function (Email $email) {
    return $email->to()->has('calvin@snicco.io')
});

```

**Intercepting WordPress emails**

In addition to faking emails send by your own code that uses the `Mailer` class, the `FakeTransport` also lets
you fake all other emails that are sent directly by using `wp_mail`.

```php
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\Testing\WPMail;

$transport = new FakeTransport()

$transport->interceptWordPressEmails();

// This will pass
$transport->assertNotSent(WPMail::class);

// No emails will be sent here.
wp_mail('calvin@snicco.io', 'Hi calvin', 'Testing WordPress emails was never this easy...');

// This will now fail.
$transport->assertNotSent(WPMail::class);

// This will pass
$transport->assertSent(WPMail::class);

// This will pass
$transport->assertSent(WPMail::class, function (WPMail $mail) {
    return 'Hi calvin' === $mail->subject();
});

// This will fail
$transport->assertSent(WPMail::class, function (WPMail $mail) {
    return 'Hi marlon' === $mail->subject();
});

```

## Contributing

This repository is a read-only split of the development repo of the
[**Snicco** project](https://github.com/sniccowp/sniccowp).

[This is how you can contribute](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md).

## Reporting issues and sending pull requests

Please report issues in the
[**Snicco** monorepo](https://github.com/sniccowp/sniccowp/blob/master/CONTRIBUTING.md##using-the-issue-tracker).

## Security

If you discover a security vulnerability within **BetterWPHooks**, please follow
our [disclosure procedure](https://github.com/sniccowp/sniccowp/blob/master/SECURITY.md).
