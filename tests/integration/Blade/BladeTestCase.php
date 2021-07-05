<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Illuminate\Support\Facades\Facade;
    use Tests\TestCase;
    use WPEmerge\Blade\BladeDirectiveServiceProvider;
    use WPEmerge\Blade\BladeServiceProvider;

    class BladeTestCase extends TestCase
    {

        protected static $ignore_files = array();

        protected function setUp() : void
        {

            parent::setUp();

            // $this->rmdir(BLADE_CACHE);
        }

        protected function tearDown() : void
        {
            Facade::clearResolvedInstances();
            parent::tearDown();
        }

        public function packageProviders() : array
        {

            return [
                BladeServiceProvider::class,
                BladeDirectiveServiceProvider::class
            ];
        }

    }