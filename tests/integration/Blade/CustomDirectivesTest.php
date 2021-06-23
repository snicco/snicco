<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use Illuminate\Support\MessageBag;
    use Illuminate\Support\ViewErrorBag;
    use Slim\Csrf\Guard;
    use Tests\integration\Blade\traits\AssertBladeView;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\Session\Session;

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

            TestApp::container()->bind(CsrfField::class, function () {

                $storage = [];

                return new CsrfField(
                    new Session( new ArraySessionDriver(10)),
                    new Guard(
                        TestApp::container()->make(ResponseFactory::class),
                        'csrf', $storage
                    )

                );

            });

            $view = $this->view('csrf');
            $content = $view->toString();

            $this->assertStringStartsWith('<input', $content);
            $this->assertStringContainsString('csrf_name', $content);
            $this->assertStringContainsString('csrf_value', $content);

        }

        /** @test */
        public function method_directive_works () {

            $view = $this->view('method');
            $content = $view->toString();
            $this->assertViewContent("<input type='hidden' name='_method' value='PUT'>", $content);

        }

        /** @test */
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

        /** @test */
        public function errors_work_with_custom_error_bags () {

            // Named error bag.
            $error_bag = new ViewErrorBag();
            $custom = new MessageBag();
            $custom->add('title', 'CUSTOM_BAG_ERROR');
            $error_bag->put('custom',$custom);
            $view = $this->view('error-custom-bag');
            $view->with('errors', $error_bag);

            $this->assertViewContent('CUSTOM_BAG_ERROR', $view);

            // Wrong Named error bag.
            $error_bag = new ViewErrorBag();
            $bogus = new MessageBag();
            $bogus->add('title', 'CUSTOM_BAG_ERROR');
            $error_bag->put('bogus',$bogus);
            $view = $this->view('error-custom-bag');
            $view->with('errors', $error_bag);

            $this->assertViewContent('NO ERRORS IN CUSTOM BAG', $view);

        }

        private function view(string $view) : ViewInterface
        {

            return TestApp::view('blade-features.'.$view);

        }


    }