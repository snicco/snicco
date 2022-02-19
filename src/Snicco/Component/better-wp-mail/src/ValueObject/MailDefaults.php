<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObject;

use Snicco\Component\BetterWPMail\ScopableWP;

final class MailDefaults
{

    private string $from_name;
    private string $from_email;
    private string $reply_to_name;
    private string $reply_to_email;

    public function __construct(string $from_email, string $from_name, string $reply_to_email, string $reply_to_name)
    {
        $this->from_email = $from_email;
        $this->from_name = $from_name;
        $this->reply_to_name = $reply_to_name;
        $this->reply_to_email = $reply_to_email;
    }

    public static function fromWordPressSettings(ScopableWP $wp = null): MailDefaults
    {
        $wp = $wp ?: new ScopableWP();

        $email = $wp->adminEmail();
        $name = $wp->siteName();

        return new MailDefaults(
            $email,
            $name,
            $email,
            $name,
        );
    }

    public function getFrom(): Mailbox
    {
        return Mailbox::create([$this->from_email, $this->from_name]);
    }

    public function getReplyTo(): Mailbox
    {
        return Mailbox::create([$this->reply_to_email, $this->reply_to_name]);
    }

}