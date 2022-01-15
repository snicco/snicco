<?php

declare(strict_types=1);

namespace Tests\HttpRouting;

use Snicco\Testing\MiddlewareTestCase;
use Tests\Codeception\shared\helpers\CreateContainer;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;

class InternalMiddlewareTestCase extends MiddlewareTestCase
{
    
    use CreatePsr17Factories;
    use CreateContainer;
}