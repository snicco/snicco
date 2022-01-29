<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests;

use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsrContainer;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

class InternalMiddlewareTestCase extends MiddlewareTestCase
{
    
    use CreateTestPsr17Factories;
    use CreateTestPsrContainer;
}