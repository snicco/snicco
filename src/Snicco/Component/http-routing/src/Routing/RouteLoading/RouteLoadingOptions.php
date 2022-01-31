<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RouteLoading;

/**
 * @api
 */
interface RouteLoadingOptions
{

    public function getApiRouteAttributes(
        string $file_name_without_extension_and_version,
        ?string $parsed_version
    ): array;

    public function getRouteAttributes(string $file_name_without_extension): array;

}