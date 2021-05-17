<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use PHPUnit\Framework\Assert;
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

            parent::tearDown();

        }

        private function newApp()
        {


            $this->rmdir(BLADE_CACHE);

            $this->newTestApp([
                'providers' => [
                    BladeServiceProvider::class,
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

            Assert::assertSame($expected, trim($actual));

        }

        private function clearViewCache() {

            $this->rmdir(BLADE_CACHE);

        }

    }