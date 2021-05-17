<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Illuminate\Support\Facades\Blade;
    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;

    class BladeFeaturesTest extends IntegrationTest
    {

        use AssertBladeView;

        /** @test */
        public function xss_protection_works()
        {

            $view = $this->view('xss');

            $this->assertStringStartsWith('&lt;script', $view->toString());

        }

        /** @test */
        public function xss_encoding_can_be_disabled()
        {

            $view = $this->view('xss-disabled')
                         ->with('script', '<script type="text/javascript">alert("Hacked!");</script>');

            $this->assertStringStartsWith('<script', $view->toString());

        }

        /** @test */
        public function json_works()
        {

            $view = $this->view('json')->with('json', ['foo' => 'bar']);
            $content = $view->toString();
            $this->assertSame(['foo' => 'bar'], json_decode($content, true));


        }

        /** @test */
        public function if_works()
        {

            $view = $this->view('if')->with('records', ['foo']);
            $content = $view->toString();
            $this->assertViewContent('I have one record!', $content);


            $view = $this->view('if')->with('records', ['foo','bar']);
            $content = $view->toString();
            $this->assertViewContent('I have multiple records!', $content);


            $view = $this->view('if')->with('records', []);
            $content = $view->toString();
            $this->assertViewContent("I don't have any records!", $content);


        }

        /** @test */
        public function unless_works () {

            $view = $this->view('unless')->with('foo', 'foo');
            $content = $view->toString();
            $this->assertViewContent('', $content);


            $view = $this->view('unless')->with('foo', 'bar');
            $content = $view->toString();
            $this->assertViewContent('UNLESS', $content);

        }

        /** @test */
        public function empty_isset_works () {

            $view = $this->view('isset-empty')->with('isset', 'foo')->with('empty', 'blabla');
            $content = $view->toString();
            $this->assertViewContent('ISSET', $content);

            $view = $this->view('isset-empty')->with('empty', '');
            $content = $view->toString();
            $this->assertViewContent('EMPTY', $content);



        }

        /** @test */
        public function custom_auth_directive_works () {

            $foo = 'bar';

            Blade::directive('foo', function () {

            });

            $user = $this->factory()->user->create();
            wp_set_current_user($user);

            $view = $this->view('auth');
            $content = $view->toString();
            $this->assertViewContent('AUTH', $content);


        }



        private function view(string $view)
        {

            return TestApp::view('blade-features.'.$view);

        }



    }