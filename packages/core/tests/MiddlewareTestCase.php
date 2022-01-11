<?php

declare(strict_types=1);

namespace Tests\Core;

use Snicco\Core\Routing\UrlGenerator;
use Snicco\Core\Routing\Internal\Generator;
use Snicco\Core\Routing\Internal\RFC3986Encoder;
use Snicco\Core\Routing\Internal\WPAdminDashboard;
use Tests\Codeception\shared\helpers\CreateContainer;
use Snicco\Core\Routing\Internal\UrlGenerationContext;
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
        
        return new Generator(
            $this->routes(),
            $context,
            WPAdminDashboard::fromDefaults(),
            new RFC3986Encoder()
        );
    }
    
}