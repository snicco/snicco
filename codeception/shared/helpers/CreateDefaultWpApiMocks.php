<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\Core\Utils\WP;

/**
 * @internal
 */
trait CreateDefaultWpApiMocks
{
    
    protected function createDefaultWpApiMocks()
    {
        WP::shouldReceive('fileHeaderData')->andReturn([])->byDefault();
        WP::shouldReceive('wpAdminFolder')->andReturn('wp-admin')->byDefault();
        WP::shouldReceive('ajaxUrl')->andReturn('wp-admin/admin-ajax.php')->byDefault();
        WP::shouldReceive('adminUrl')->andReturnUsing(function (string $path) {
            return trim(SITE_URL, '/').DS.'wp-admin'.DS.$path;
        })->byDefault();
        WP::shouldReceive('mail')->andReturnTrue()->byDefault();
        WP::shouldReceive('siteName')->andReturn('WP MVC')->byDefault();
        WP::shouldReceive('adminEmail')->andReturn('c@web.de')->byDefault();
        WP::shouldReceive('userId')->andReturn(1)->byDefault();
        WP::shouldReceive('usesTrailingSlashes')->andReturnFalse()->byDefault();
        WP::shouldReceive('siteUrl')->andReturn(SITE_URL)->byDefault();
        WP::shouldReceive('removeFilter')->andReturnTrue()->byDefault();
    }
    
}