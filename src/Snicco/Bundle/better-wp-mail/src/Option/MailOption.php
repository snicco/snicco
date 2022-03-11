<?php

declare(strict_types=1);

namespace Snicco\Bundle\BetterWPMail\Option;

final class MailOption
{
    /**
     * @var string
     */
    public const FROM_NAME = 'from_name';

    /**
     * @var string
     */
    public const FROM_EMAIL = 'from_email';

    /**
     * @var string
     */
    public const REPLY_TO_NAME = 'reply_to_name';

    /**
     * @var string
     */
    public const REPLY_TO_EMAIL = 'reply_to_email';

    /**
     * @var string
     */
    public const RENDERER = 'renderer';

    /**
     * @var string
     */
    public const TRANSPORT = 'transport';

    /**
     * @var string
     */
    public const EXPOSE_MAIL_EVENTS = 'expose_events';
}
