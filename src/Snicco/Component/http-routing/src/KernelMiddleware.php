<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Http\Server\MiddlewareInterface;
use Snicco\Middleware\MethodOverride\MethodOverride;
use Snicco\Component\HttpRouting\Http\NegotiateContent;

final class KernelMiddleware
{
    
    private PrepareResponse   $prepare_response;
    private RoutingMiddleware $routing;
    private RouteRunner       $route_runner;
    private MethodOverride    $method_override;
    private NegotiateContent  $negotiate_content;
    
    public function __construct(
        NegotiateContent $negotiate_content,
        PrepareResponse $prepare_response,
        MethodOverride $method_override,
        RoutingMiddleware $routing,
        RouteRunner $route_runner
    ) {
        $this->negotiate_content = $negotiate_content;
        $this->prepare_response = $prepare_response;
        $this->method_override = $method_override;
        $this->routing = $routing;
        $this->route_runner = $route_runner;
    }
    
    /**
     * @return MiddlewareInterface[]
     */
    public function asArray() :array
    {
        return [
            $this->negotiate_content,
            $this->prepare_response,
            $this->method_override,
            $this->routing,
            $this->route_runner,
        ];
    }
    
}