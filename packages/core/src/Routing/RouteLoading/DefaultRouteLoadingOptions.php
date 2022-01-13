<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\RouteLoading;

use Snicco\Support\Str;
use Snicco\Core\Support\UrlPath;
use Snicco\Core\Routing\RouteLoader;
use Snicco\Core\Routing\RoutingConfigurator\RoutingConfigurator;

/**
 * @api
 */
final class DefaultRouteLoadingOptions implements RouteLoadingOptions
{
    
    private UrlPath $api_base_prefix;
    private bool    $add_middleware_for_api_files;
    
    public function __construct(string $base_api_prefix, bool $add_middleware_for_each_api_file = false)
    {
        $this->api_base_prefix = UrlPath::fromString($base_api_prefix);
        $this->add_middleware_for_api_files = $add_middleware_for_each_api_file;
    }
    
    public function getApiRouteAttributes(string $file_name_without_extension_and_version, ?string $parsed_version) :array
    {
        if ($parsed_version) {
            $_name = Str::before(
                $file_name_without_extension_and_version,
                RouteLoader::VERSION_FLAG
            );
            $file_name_without_extension_and_version = $_name.".v$parsed_version";
            $prefix = (string) $this->api_base_prefix->append($_name)->append("v$parsed_version");
        }
        else {
            $prefix = $this->api_base_prefix->append($file_name_without_extension_and_version)
                                            ->asString();
        }
        
        $api_middleware = ['api'];
        if ($this->add_middleware_for_api_files) {
            $api_middleware[] = $file_name_without_extension_and_version;
        }
        
        return [
            RoutingConfigurator::PREFIX_KEY => $prefix,
            RoutingConfigurator::MIDDLEWARE_KEY => $api_middleware,
            RoutingConfigurator::NAME_KEY => 'api.'.$file_name_without_extension_and_version,
        ];
    }
    
    public function getRouteAttributes($file_name_without_extension) :array
    {
        $att = [];
        
        if (RouteLoader::ADMIN_ROUTE_FILENAME === $file_name_without_extension) {
            $att[RoutingConfigurator::MIDDLEWARE_KEY] = [RoutingConfigurator::ADMIN_MIDDLEWARE];
            $att[RoutingConfigurator::NAME_KEY] = 'admin';
        }
        
        if (RouteLoader::FRONTEND_ROUTE_FILENAME === $file_name_without_extension) {
            $att[RoutingConfigurator::MIDDLEWARE_KEY] = [RoutingConfigurator::FRONTEND_MIDDLEWARE];
            $att[RoutingConfigurator::NAME_KEY] = 'web';
        }
        
        return $att;
    }
    
}