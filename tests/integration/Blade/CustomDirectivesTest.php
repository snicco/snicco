<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Illuminate\Support\MessageBag;
    use Illuminate\Support\ViewErrorBag;
    use Tests\integration\Blade\traits\AssertBladeView;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\integration\IntegrationTest;
    use Tests\stubs\TestApp;

    class CustomDirectivesTest extends IntegrationTest
    {

        use AssertBladeView;
        use InteractsWithWordpress;

        /** @test */
        public function custom_auth_user_directive_works()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $view = $this->view('auth');
            $content = $view->toString();
            $this->assertViewContent('AUTHENTICATED', $content);

            $this->logout($calvin);
            $view = $this->view('auth');
            $content = $view->toString();
            $this->assertViewContent('', $content);

        }

        /** @test */
        public function custom_guest_user_directive_works()
        {

            $view = $this->view('guest');
            $content = $view->toString();
            $this->assertViewContent('YOU ARE A GUEST', $content);

            $calvin = $this->newAdmin();
            $this->login($calvin);

            $view = $this->view('guest');
            $content = $view->toString();
            $this->assertViewContent('', $content);

        }

        /** @test */
        public function custom_wp_role_directives_work()
        {

            $admin = $this->newAdmin();
            $this->login($admin);
            $view = $this->view('role');
            $content = $view->toString();
            $this->assertViewContent('ADMIN', $content);

            $this->logout($admin);

            $editor = $this->newEditor();
            $this->login($editor);
            $view = $this->view('role');
            $content = $view->toString();
            $this->assertViewContent('EDITOR', $content);

            $author = $this->newAuthor();
            $this->login($author);
            $view = $this->view('role');
            $content = $view->toString();
            $this->assertViewContent('', $content);


        }

        /** @test */
        public function custom_csrf_directives_work () {

            /** @todo Decide how to implement with CSRF middleware */

            $calvin = $this->newAdmin();
            $john = $this->newAdmin();

            $this->login($calvin);
            $view1 = $this->view('csrf');
            $content1 = $view1->toString();

            $this->logout($calvin);

            $this->login($john);
            $view2 = $this->view('csrf');
            $content2 = $view2->toString();

            $this->assertNotSame($content1, $content2);
            $this->assertStringStartsWith('<input', $content1);
            $this->assertStringStartsWith('<input', $content2);


        }

        /** @test */
        public function method_directive_works () {

            $view = $this->view('method');
            $content = $view->toString();
            $this->assertViewContent("<input type='hidden' name='_method' value='PUT'>", $content);

        }

        /**
         * @test
         *
         */
        public function error_directive_works () {

            /** @todo Decide how to implement with sessions and compatible with the default php engine. */
            $error_bag = new ViewErrorBag();
            $default = new MessageBag();
            $default->add('title', 'ERROR_WITH_YOUR_TITLE');
            $error_bag->put('default',$default);
            $view = $this->view('error');
            $view->with('errors', $error_bag);

            $this->assertViewContent('ERROR_WITH_YOUR_TITLE', $view);

            $view = $this->view('error');
            $error_bag = new ViewErrorBag();
            $default = new MessageBag();
            $error_bag->put('default',$default);
            $view->with('errors', $error_bag);

            $this->assertViewContent('NO ERRORS WITH YOUR VIEW', $view);

        }

        private function view(string $view)
        {

            return TestApp::view('blade-features.'.$view);

        }


    }