<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Illuminate\Support\Facades\Blade;
    use Tests\integration\Blade\Components\Alert;
    use Tests\integration\Blade\Components\AlertAttributes;
    use Tests\integration\Blade\Components\Dependency;
    use Tests\integration\Blade\Components\HelloWorld;
    use Tests\integration\Blade\Components\InlineComponent;
    use Tests\integration\Blade\Components\ToUppercaseComponent;
    use Tests\integration\Blade\traits\AssertBladeView;
    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;

    class BladeLayoutsTest extends IntegrationTest
    {

        use AssertBladeView;

        /** @test */
        public function layouts_and_extending_work () {

            $view = TestApp::view('layouts.child');

            $this->assertViewContent(
                'Name:foo,SIDEBAR:parent_sidebar.appended,BODY:foobar,FOOTER:default_footer',
                $view->toString()
            );

        }


        /** @test */
        public function stacks_work () {

            $view = TestApp::view('stack-child');
            $content = $view->toString();
            $this->assertViewContent('FOOBAZBAR', $content);

        }

    }