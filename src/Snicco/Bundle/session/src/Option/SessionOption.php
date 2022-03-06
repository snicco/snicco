<?php

declare(strict_types=1);


namespace Snicco\Bundle\Session\Option;

use Snicco\Bundle\Session\Middleware\SaveResponseAttributes;
use Snicco\Bundle\Session\Middleware\ShareSessionWithViews;
use Snicco\Bundle\Session\Middleware\StatefulRequest;
use Snicco\Component\Session\ValueObject\SessionConfig;

final class SessionOption
{
    /**
     * @see SessionConfig::mergeDefaults()
     */
    public const CONFIG = 'config';

    public const COOKIE_NAME = 'cookie_name';
    public const DRIVER = 'driver';
    public const PREFIX = 'prefix';
    public const ENCRYPT_DATA = 'encrypt';
    public const SERIALIZER = 'serializer';

    public const MIDDLEWARE_GROUP = [
        StatefulRequest::class,
        SaveResponseAttributes::class,
        ShareSessionWithViews::class,
    ];
}