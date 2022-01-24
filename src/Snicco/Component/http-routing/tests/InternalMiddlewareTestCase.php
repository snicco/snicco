<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests;

use Snicco\Testing\MiddlewareTestCase;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

class InternalMiddlewareTestCase extends MiddlewareTestCase
{
    
    use CreateTestPsr17Factories;
    use CreateContainer;
}