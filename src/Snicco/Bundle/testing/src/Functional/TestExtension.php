<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Functional;

interface TestExtension
{
    public function setUp(): void;

    public function tearDown(): void;
}
