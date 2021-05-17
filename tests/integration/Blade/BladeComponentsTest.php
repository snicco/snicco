<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Illuminate\Support\Facades\Blade;
    use Tests\integration\Blade\Components\Alert;
    use Tests\integration\Blade\Components\HelloWorld;
    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;

    class BladeComponentsTest extends IntegrationTest
    {

        use AssertBladeView;

        /** @test */
        public function basic_anonymous_components_work () {

            $view = TestApp::view('anonymous-component');
            $content = $view->toString();
            $this->assertViewContent('Hello World', $content);

        }

        // /** @test */
        public function basic_class_based_components_work () {

            Blade::component(HelloWorld::class, 'hello-world');

            $view = TestApp::view('class-component');
            $content = $view->toString();
            $this->assertViewContent('Hello World Class BladeComponent', $content);

        }

        // /** @test */
        public function class_components_can_pass_data () {

            Blade::component(Alert::class, 'alert');

            $view = TestApp::view('alert-component')->with('message', 'foo');
            $content = $view->toString();
            $this->assertViewContent('TYPE:error,MESSAGE:foo', $content);

        }

    }