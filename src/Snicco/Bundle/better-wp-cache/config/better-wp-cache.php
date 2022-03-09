<?php

declare(strict_types=1);

use Snicco\Bundle\BetterWPCache\Option\BetterWPCacheOption;

return [

    /*
     * You must set a unique value here so that your cache values don't clash in the shared wp object cache.
     */
    BetterWPCacheOption::CACHE_GROUP => 'my_plugin_cache_prefix_12312432'

];
