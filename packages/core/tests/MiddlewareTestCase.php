<?php

declare(strict_types=1);

namespace Tests\Core;

use Snicco\Core\Contracts\UrlGeneratorInterface;
use Tests\Codeception\shared\helpers\CreateContainer;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Snicco\Testing\MiddlewareTestCase as FrameworkMiddlewareTestCase;

class MiddlewareTestCase extends FrameworkMiddlewareTestCase
{
    
    use CreatePsr17Factories;
    use CreateContainer;
    
    protected function urlGenerator() :UrlGeneratorInterface
    {
        return $this->newUrlGenerator();
    }
    
}