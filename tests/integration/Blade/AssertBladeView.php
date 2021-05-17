<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Illuminate\Support\Facades\Facade;
    use PHPUnit\Framework\Assert;
    use WPEmerge\Blade\BladeDirectiveServiceProvider;
    use WPEmerge\Blade\BladeServiceProvider;
    use WPEmerge\Contracts\ViewInterface;

    trait AssertBladeView
    {

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

        private function clearViewCache() {

            $this->rmdir(BLADE_CACHE);

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
                    ],
                ],
            ]);

        }

        public function assertViewContent(string $expected,  $actual) {

            $actual = ($actual instanceof ViewInterface) ? $actual->toString() :$actual;

            $actual = preg_replace( "/\r|\n/", "", $actual );

            Assert::assertSame($expected, trim($actual), 'View not rendered correctly.');

        }





    }