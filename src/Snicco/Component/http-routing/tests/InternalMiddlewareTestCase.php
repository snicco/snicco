<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests;

use Test\Helpers\CreateContainer;
use Snicco\Testing\MiddlewareTestCase;
use Snicco\Component\HttpRouting\Tests\helpers\CreatePsr17Factories;

class InternalMiddlewareTestCase extends MiddlewareTestCase
{
    
    use CreatePsr17Factories;
    use CreateContainer;
}