<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObject;

use Snicco\Component\BetterWPMail\WPMailAPI;

final class MailDefaults
{
    private string $from_name;
    private string $from_email;
    private string $reply_to_name;
    private string $reply_to_email;

    public function __construct(
        string $from_email = '',
        string $from_name = '',
        string $reply_to_email = '',
        string $reply_to_name = ''
    ) {
        $wp = new WPMailAPI();

        $this->from_email = empty($from_email) ? $wp->adminEmail() : $from_email;
        $this->from_name = empty($from_name) ? $wp->siteName() : $from_name;
        $this->reply_to_name = empty($reply_to_name) ? $this->from_name : $reply_to_name;
        $this->reply_to_email = empty($reply_to_email) ? $this->from_email : $reply_to_email;
    }

    public static function fromWordPressSettings(WPMailAPI $wp = null): MailDefaults
    {
        $wp = $wp ?: new WPMailAPI();

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
