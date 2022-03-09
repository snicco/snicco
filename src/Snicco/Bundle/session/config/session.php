<?php

declare(strict_types=1);

use Snicco\Bridge\SessionWP\WPDBSessionDriver;
use Snicco\Bundle\Session\Option\SessionOption;
use Snicco\Component\Session\Serializer\JsonSerializer;

return [
    /*
     * The name of the session cookie.
     * If you don't need JavaScript access to this cookie you can leave this value as is. It was randomly generated when the configuration was first copied.
     */
    SessionOption::COOKIE_NAME => 'set-a-random-name-here',

    /*
     * There are many options to customize the session behaviour.
     * For a full list of options see SessionConfig::mergeDefaults()
     */
    SessionOption::CONFIG => [

        // The path where the session cookie is available.
        // You should leave this option as is. Otherwise, the cookie won't be available if users are logged-in/logged-out.
        'path' => '/',

        // Must be one of "Lax"|"Strict"|"None".
        'same_site' => 'Lax',

        // Settings this value to false will mean the cookie is accessible via Javascript. This is not recommended unless you have a very good reason.
        'http_only' => true,

        // Only send the cookie over https. Don't turn this off unless you are developing locally with http.
        'secure' => true,

        // The maximum lifetime of a session independently of activity.
        // Setting this value to null means that the session will be deleted after closing the browser.
        // Settings this value to "60*30" will automatically be invalidated after 30 minutes.
        'absolute_timeout_in_sec' => null,

        // After 15 minutes without activity (visiting new pages, performing requests etc.) the session will be invalidated.
        'idle_timeout_in_sec' => 60 * 15,

        // The session id will be rotated every 10 minutes. The session content stays the same.
        'rotation_interval_in_sec' => 60 * 10,

        // The percentage that for a given request garbage collection of old sessions will be performed.
        'garbage_collection_percentage' => 2,
    ],

    /*
     * The prefix will be used to for creating the session table in the database or as a cache group if
     * using the object cache as a driver
     */
    SessionOption::PREFIX => 'my_plugin_sessions',

    /*
     * The session driver that will be used to store the sessions.
     * During tests, an in-memory driver will be used automatically.
     * If you use the WPObjectCacheDriver you need to make sure that your WordPress cache plugin is persistent.
     */
    SessionOption::DRIVER => WPDBSessionDriver::class,
    //    SessionOption::DRIVER => WPObjectCacheDriver::class,

    /*
     * If your session data contains sensitive information you can encrypt the session content
     * by setting this option to true.
     * If you set this option to true you will need to also add the encryption-bundle to your bundles.php file.
     */
    SessionOption::ENCRYPT_DATA => false,

    /*
     * The session serializer is responsible for transforming the session contents into a string.
     * If you don't absolutely MUST store PHP objects in the session you should leave this option as is.
     * Otherwise you can use the PHPSerializer.
     */
    SessionOption::SERIALIZER => JsonSerializer::class,
    //    SessionOption::SERIALIZER => PHPSerializer::class,
];
