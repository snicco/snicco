<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Tests\TestCase;
use Snicco\Blade\BladeServiceProvider;
use Snicco\Blade\BladeDirectiveServiceProvider;

class BladeTestCase extends TestCase
{
	
	protected static $ignore_files = [];
	
	protected function setUp() :void
	{
		
		parent::setUp();
		$this->rmdir(BLADE_CACHE);
	}
	
	public function packageProviders() :array
	{
		
		return [
			BladeServiceProvider::class,
			BladeDirectiveServiceProvider::class,
		];
	}
	
}