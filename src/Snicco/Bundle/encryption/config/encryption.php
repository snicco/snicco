<?php

declare(strict_types=1);

use Snicco\Bundle\Encryption\Option\EncryptionOption;

return [

    /*
     * The value of this option is HIGHLY SENSITIVE. Please read the following instructions carefully.
     *
     * 1) If you are developing for an environment that you control:
     *    - generate a key by running 'vendor/bin/generate-defuse-key'
     *    - Load this value from an environment variable and ENSURE that the cache directory of your app is outside your webroot.
     *
     * 2) If you are distributing code for an environment you don't control:
     *    - generate a key by running 'DefuseEncryptor::randomKey' during your plugin installation process and save
     *      the output to a constant in the "wp-config.php" file.
     *    - ENSURE that the cache directory of your plugin is not accessible publicly. How you ensure that is up to you.
     *      Since you can't control your users web-server configuration two approaches are:
     *          a) Only run your plugin if a separate constant is set along the lines of define("MY_PLUGIN_CACHE_DIR_IS_SECURE, true")
     *          b) Implement a custom config cache class that will only load the data if ABSPATH is defined.
     *
     * This value SHOULD NEVER EVER EVER EVER be committed into version control. 
     */
    EncryptionOption::KEY_ASCII => 'this-key-will-throw-an-exception'

];
