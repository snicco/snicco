<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RouteLoader;

interface RouteLoadingOptions
{
    /**
     * @return  array{
     *          namespace?:string,
     *          prefix?:string,
     *          name?:string,
     *          middleware?: string[]
     *          }
     */
    public function getApiRouteAttributes(string $file_basename, ?string $parsed_version): array;

    /**
     * @return  array{
     *          namespace?:string,
     *          prefix?:string,
     *          name?:string,
     *          middleware?: string[]
     *          }
     */
    public function getRouteAttributes(string $file_basename): array;
}
