<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Snicco\Blade\BladeDirectiveServiceProvider;
    use Snicco\Blade\BladeServiceProvider;
    use Tests\TestCase;

    class BladeTestCase extends TestCase
    {

        protected static $ignore_files = array();

        protected function setUp() : void
        {

            parent::setUp();

        }



        public function packageProviders() : array
        {

            return [
                BladeServiceProvider::class,
                BladeDirectiveServiceProvider::class
            ];
        }

    }