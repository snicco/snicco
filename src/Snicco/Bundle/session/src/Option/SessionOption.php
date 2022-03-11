<?php

declare(strict_types=1);

namespace Snicco\Bundle\Session\Option;

use Psr\Http\Server\MiddlewareInterface;
use Snicco\Bundle\Session\Middleware\SaveResponseAttributes;
use Snicco\Bundle\Session\Middleware\ShareSessionWithViews;
use Snicco\Bundle\Session\Middleware\StatefulRequest;
use Snicco\Component\Session\ValueObject\SessionConfig;

final class SessionOption
{
    /**
     * @see SessionConfig::mergeDefaults()
     *
     * @var string
     */
    public const CONFIG = 'config';

    /**
     * @var string
     */
    public const COOKIE_NAME = 'cookie_name';

    /**
     * @var string
     */
    public const DRIVER = 'driver';

    /**
     * @var string
     */
    public const PREFIX = 'prefix';

    /**
     * @var string
     */
    public const ENCRYPT_DATA = 'encrypt';

    /**
     * @var string
     */
    public const SERIALIZER = 'serializer';

    /**
     * @var array<class-string<MiddlewareInterface>>
     */
    public const MIDDLEWARE_GROUP = [
        StatefulRequest::class,
        SaveResponseAttributes::class,
        ShareSessionWithViews::class,
    ];
}
