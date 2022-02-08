<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RouteLoading;

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Snicco\Component\StrArr\Str;

/**
 * @api
 */
final class DefaultRouteLoadingOptions implements RouteLoadingOptions
{

    private UrlPath $api_base_prefix;
    private bool $add_middleware_for_api_files;

    public function __construct(string $base_api_prefix, bool $add_middleware_for_each_api_file = false)
    {
        $this->api_base_prefix = UrlPath::fromString($base_api_prefix);
        $this->add_middleware_for_api_files = $add_middleware_for_each_api_file;
    }

    public function getApiRouteAttributes(string $file_basename, ?string $parsed_version): array
    {
        if ($parsed_version) {
            $_name = Str::beforeFirst(
                $file_basename,
                PHPFileRouteLoader::VERSION_FLAG
            );
            $file_basename = $_name . ".v$parsed_version";
            $prefix = (string)$this->api_base_prefix->append($_name)->append("v$parsed_version");
        } else {
            $prefix = $this->api_base_prefix->append($file_basename)->asString();
        }

        $api_middleware = ['api'];
        if ($this->add_middleware_for_api_files) {
            $api_middleware[] = $file_basename;
        }

        return [
            RoutingConfigurator::PREFIX_KEY => $prefix,
            RoutingConfigurator::MIDDLEWARE_KEY => $api_middleware,
            RoutingConfigurator::NAME_KEY => 'api.' . $file_basename,
        ];
    }

    public function getRouteAttributes(string $file_basename): array
    {
        $att = [];

        if (PHPFileRouteLoader::ADMIN_ROUTE_FILENAME === $file_basename) {
            $att[RoutingConfigurator::MIDDLEWARE_KEY] = [RoutingConfigurator::ADMIN_MIDDLEWARE];
            $att[RoutingConfigurator::NAME_KEY] = 'admin';
        }

        if (PHPFileRouteLoader::FRONTEND_ROUTE_FILENAME === $file_basename) {
            $att[RoutingConfigurator::MIDDLEWARE_KEY] = [RoutingConfigurator::FRONTEND_MIDDLEWARE];
        }
        return $att;
    }

}