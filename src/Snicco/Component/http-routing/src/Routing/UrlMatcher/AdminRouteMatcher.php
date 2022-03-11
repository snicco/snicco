<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\Admin\AdminArea;

/**
 * @interal
 * @psalm-internal Snicco\Component\HttpRouting
 */
final class AdminRouteMatcher implements UrlMatcher
{
    private UrlMatcher $url_matcher;

    private AdminArea $admin_area;

    public function __construct(UrlMatcher $url_matcher, AdminArea $admin_area)
    {
        $this->url_matcher = $url_matcher;
        $this->admin_area = $admin_area;
    }

    public function dispatch(Request $request): RoutingResult
    {
        return $this->url_matcher->dispatch($this->allowMatchingAdminDashboardRequests($request));
    }

    private function allowMatchingAdminDashboardRequests(Request $request): Request
    {
        if (! $request->isGet()) {
            return $request;
        }

        if (! $request->isToAdminArea()) {
            return $request;
        }

        $uri = $request->getUri();
        $new_uri = $uri->withPath($this->admin_area->rewriteForRouting($request));

        return $request->withUri($new_uri);
    }
}
