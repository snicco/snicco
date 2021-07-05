<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade\traits;

    use Illuminate\Support\Facades\Facade;
    use PHPUnit\Framework\Assert;
    use BetterWP\Blade\BladeDirectiveServiceProvider;
    use BetterWP\Blade\BladeServiceProvider;
    use BetterWP\Contracts\ViewInterface;

    use const BLADE_CACHE;
    use const BLADE_VIEWS;
    use const DS;

    trait AssertBladeView
    {

        private function clearViewCache() {

            $this->rmdir(BLADE_CACHE);

        }

        protected function setUp() : void
        {

            parent::setUp();

            $this->newApp();

        }

        protected function tearDown() : void
        {

            $this->clearViewCache();

            Facade::clearResolvedInstances();

            parent::tearDown();


        }

        private function newApp()
        {

            $this->clearViewCache();

            $this->newTestApp([
                'providers' => [
                    BladeServiceProvider::class,
                    BladeDirectiveServiceProvider::class
                ],
                'blade' => [
                    'cache' => BLADE_CACHE,
                    'views' => [
                        BLADE_VIEWS,
                        BLADE_VIEWS . DS . 'blade-features',
                        BLADE_VIEWS . DS . 'layouts',
                    ],
                ],
            ]);

        }



    }