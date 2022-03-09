<?php

declare(strict_types=1);

use Snicco\Bundle\Debug\Option\DebugOption;

return [
    /*
     * Set this value to the name of your editor to automatically open files.
     * A valid list of values can be found here:
     * https://github.com/filp/whoops/blob/master/docs/Open%20Files%20In%20An%20Editor.md
     */
    DebugOption::EDITOR => 'phpstorm',

    /*
     * This option has to be a list of directories which should be considered to belong to your application.
     * Typically you'll want to set this to include all directories in your project directory expect your vendor folder.
     * This will be set automatically as the default if you leave this option commented out.
     */
    //    DebugOption::APPLICATION_PATHS => [
    //        dirname(__DIR__).'/src'
    //    ]
];
