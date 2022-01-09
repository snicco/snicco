<?php

declare(strict_types=1);

namespace Tests\Core;

use Snicco\Core\Routing\UrlGenerator;
use Snicco\Core\Routing\Internal\Generator;
use Snicco\Core\Routing\Internal\RequestContext;
use Snicco\Core\Routing\Internal\RFC3986Encoder;
use Snicco\Core\Routing\Internal\WPAdminDashboard;
use Tests\Codeception\shared\helpers\CreateContainer;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Snicco\Testing\MiddlewareTestCase as FrameworkMiddlewareTestCase;

class MiddlewareTestCase extends FrameworkMiddlewareTestCase
{
    
    use CreatePsr17Factories;
    use CreateContainer;
    
    protected function urlGenerator() :UrlGenerator
    {
        return new Generator(
            $this->routes,
            $this->request_context ?? new RequestContext(
                $this->frontendRequest('GET', '/foo'),
                WPAdminDashboard::fromDefaults(),
            ),
            new RFC3986Encoder()
        );
    }
    
}