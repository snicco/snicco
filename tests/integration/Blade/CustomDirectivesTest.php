<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Tests\IntegrationTest;
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

        private function view(string $view)
        {

            return TestApp::view('blade-features.'.$view);

        }


    }