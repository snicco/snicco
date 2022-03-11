<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPMail\Option\MailOption;
use Snicco\Component\BetterWPMail\Renderer\FilesystemRenderer;
use Snicco\Component\BetterWPMail\Transport\WPMailTransport;

return [
    /*
     * The global from name that will be used by default for emails.
     * You can always set an individual value when you are sending a specific email.
     *
     * If you set this to an empty string the value will be retrieved from the WordPress options.
     */
    MailOption::FROM_NAME => '',

    /*
     * The global from email that will be used by default for emails.
     * You can always set an individual value when you are sending a specific email.
     *
     * If you set this to an empty string the value will be retrieved from the WordPress options.
     */
    MailOption::FROM_EMAIL => '',

    /*
     * The global reply-to name that will be used by default for emails.
     * You can always set an individual value when you are sending a specific email.
     *
     * If you set this to an empty string the value will be retrieved from the WordPress options.
     */
    MailOption::REPLY_TO_NAME => '',

    /*
     * The global reply-to email that will be used by default for emails.
     * You can always set an individual value when you are sending a specific email.
     *
     * If you set this to an empty string the value will be retrieved from the WordPress options.
     */
    MailOption::REPLY_TO_EMAIL => '',

    /*
     * An array of classes implementing the MailRenderer interface.
     * The first renderer that supports a given mail template will be used to render the template body.
     */
    MailOption::RENDERER => [FilesystemRenderer::class],

    /*
     * The name of a class implementing the Transport interface.
     * During testing a FakeTransport will be used automatically.
     *
     * If you are distributing code you should always leave this value as is.
     */
    MailOption::TRANSPORT => WPMailTransport::class,

    /*
     * Setting this option to (bool) true will expose mail events to the WordPress hook api.
     * If you are not distributing code you can set this value to false.
     */
    MailOption::EXPOSE_MAIL_EVENTS => false,
];
