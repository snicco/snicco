<?php

declare(strict_types=1);

namespace Tests\Core;

use Snicco\Core\Routing\UrlGenerator\UrlGenerator;
use Snicco\Core\Routing\UrlGenerator\RFC3986Encoder;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\Core\Routing\AdminDashboard\WPAdminDashboard;
use Snicco\Core\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Core\Routing\UrlGenerator\InternalUrlGenerator;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Snicco\Testing\MiddlewareTestCase as FrameworkMiddlewareTestCase;

class MiddlewareTestCase extends FrameworkMiddlewareTestCase
{
    
    use CreatePsr17Factories;
    use CreateContainer;
    
    protected function urlGenerator(UrlGenerationContext $context = null) :UrlGenerator
    {
        if (null === $context) {
            $context = $this->urlGenerationContext();
        }
        
        return new InternalUrlGenerator(
            $this->routes(),
            $context,
            WPAdminDashboard::fromDefaults(),
            new RFC3986Encoder()
        );
    }
    
}