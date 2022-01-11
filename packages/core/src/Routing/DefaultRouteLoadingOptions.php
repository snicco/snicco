<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Support\Str;

final class DefaultRouteLoadingOptions implements RouteLoadingOptions
{
    
    private UrlPath              $api_base_prefix;
    private AdminDashboardPrefix $admin_dashboard_prefix;
    
    public function __construct(string $base_api_prefix, AdminDashboardPrefix $prefix)
    {
        $this->api_base_prefix = UrlPath::fromString($base_api_prefix);
        $this->admin_dashboard_prefix = $prefix;
    }
    
    public function getApiRouteAttributes(string $file_name_without_extension_and_version, ?string $parsed_version) :array
    {
        if ($parsed_version) {
            $_name = Str::before(
                $file_name_without_extension_and_version,
                PHPFileRouteLoader::VERSION_FLAG
            );
            $file_name_without_extension_and_version = $_name.".v$parsed_version";
            $prefix = (string) $this->api_base_prefix->append($_name)->append("v$parsed_version");
        }
        else {
            $prefix = $this->api_base_prefix->append($file_name_without_extension_and_version)
                                            ->asString();
        }
        
        return [
            RoutingConfigurator::PREFIX_KEY => $prefix,
            RoutingConfigurator::MIDDLEWARE_KEY => [$file_name_without_extension_and_version],
            RoutingConfigurator::NAME_KEY => 'api.'.$file_name_without_extension_and_version,
        ];
    }
    
    public function getRouteAttributes($file_name_without_extension) :array
    {
        $att = [];
        
        if (PHPFileRouteLoader::ADMIN_ROUTES_NAME === $file_name_without_extension) {
            $att[RoutingConfigurator::MIDDLEWARE_KEY] = [RoutingConfigurator::ADMIN_MIDDLEWARE];
            $att[RoutingConfigurator::NAME_KEY] = 'admin';
        }
        
        if (PHPFileRouteLoader::WEB_ROUTES_NAME === $file_name_without_extension) {
            $att[RoutingConfigurator::MIDDLEWARE_KEY] = [RoutingConfigurator::WEB_MIDDLEWARE];
            $att[RoutingConfigurator::NAME_KEY] = 'web';
        }
        
        return $att;
    }
    
}