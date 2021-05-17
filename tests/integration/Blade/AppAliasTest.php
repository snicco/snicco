<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Blade\BladeServiceProvider;

    class AppAliasTest extends IntegrationTest
    {

        use AssertBladeView;

        protected function setUp() : void
        {

            parent::setUp();

            $this->newApp();

        }

        private function newApp()
        {

            $cache_dir = '/Users/calvinalkan/valet/wpemerge/wpemerge/tests/integration/Blade/cache';

            $this->rmdir($cache_dir);

            $this->newTestApp([
                'providers' => [
                    BladeServiceProvider::class,
                ],
                'blade' => [
                    'cache' => $cache_dir,
                    'views' => '/Users/calvinalkan/valet/wpemerge/wpemerge/tests/integration/Blade/views',
                ],
            ]);

        }

        /** @test */
        public function nested_views_can_be_rendered_relative_to_the_views_directory () {

            $view = TestApp::view('nested.view');

            $this->assertViewContent('FOO', $view );

        }

        /** @test */
        public function the_first_available_view_can_be_created () {

            $first = TestApp::view(['bogus1', 'bogus2', 'foo']);

            $this->assertViewContent('FOO', $first);

        }

    }