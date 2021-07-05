<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Controllers;

    use Respect\Validation\Validator;
    use Tests\AuthTestCase;
    use Tests\stubs\TestApp;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Auth\Contracts\CreatesNewUser;
    use WPEmerge\Auth\Contracts\DeletesUsers;
    use WPEmerge\Auth\Events\Registration;
    use WPEmerge\Auth\Responses\CreateAccountViewResponse;
    use WPEmerge\Auth\Responses\RegisteredResponse;
    use WPEmerge\Http\Psr7\Request;

    class AccountControllerTest extends AuthTestCase
    {

        protected function setUp() : void
        {

            $this->afterApplicationCreated(function () {

                $this->withAddedConfig('auth.features.registration', true);
                $this->withoutMiddleware('csrf');

                $this->instance(CreateAccountViewResponse::class, new TestCreateAccountViewResponse());
                $this->instance(RegisteredResponse::class, new TestRegisteredResponse());
                $this->instance(CreatesNewUser::class, new TestCreatesNewUser());
                $this->instance(CreatesNewUser::class, new TestCreatesNewUser());
                $this->instance(DeletesUsers::class, new TestDeletesUser());


            });
            parent::setUp();
        }

        protected function validCreateLink() : string
        {

            $this->loadRoutes();

            return TestApp::url()->signedRoute('auth.accounts.create', [], 300, true);

        }

        protected function validStoreLink() : string
        {

            $this->loadRoutes();

            return TestApp::url()->signedRoute('auth.accounts.store', [], 900);
        }

        /** @test */
        public function the_endpoint_cant_be_accessed_if_registration_is_disabled()
        {

            $this->withOutConfig('auth.features.registration');

            $response = $this->get('/auth/accounts/create');
            $response->assertNullResponse();

        }

        /** @test */
        public function the_endpoint_cant_be_accessed_authenticated()
        {

            $this->actingAs($this->createAdmin());

            $response = $this->get('/auth/accounts/create');
            $response->assertRedirectToRoute('dashboard');

        }

        /** @test */
        public function the_create_account_view_can_be_rendered()
        {

            $response = $this->get($this->validCreateLink());

            $response->assertOk()->assertSee('[Test] Create your account.');

            $store_link = $this->validStoreLink();
            $response->assertOk()->assertSee("Click here: $store_link", false);

        }

        /** @test */
        public function an_account_can_be_created()
        {

            $this->withoutExceptionHandling();
            ApplicationEvent::fake();

            $response = $this->post($this->validStoreLink(), [
                'user_login' => 'calvin',
                'user_email' => 'c@web.de',
            ]);

            $response->assertOk()->assertSee('[Test] New User: calvin');

            $user = get_user_by('login', 'calvin');
            $this->assertInstanceOf(\WP_User::class, $user);

            ApplicationEvent::assertDispatched(function (Registration $event) use ($user) {

                return $event->user->user_login === $user->user_login && $event->user->user_login === 'calvin';
            });

        }

        /** @test */
        public function an_account_can_be_deleted_from_the_same_user()
        {

            $calvin = $this->createSubscriber();
            $this->actingAs($calvin);

            $response = $this->delete("/auth/accounts/$calvin->ID");

            $response->assertStatus(204);
            $this->assertUserDeleted($calvin);



        }

        /** @test */
        public function a_user_can_only_delete_his_own_account () {

            $calvin = $this->createSubscriber();
            $john = $this->createSubscriber();
            $this->actingAs($calvin);

            $response = $this->delete("/auth/accounts/$john->ID");

            $response->assertForbidden();
            $this->assertUserNotDeleted($john);

        }

        /** @test */
        public function admins_can_delete_accounts_for_other_users () {

            $calvin = $this->createAdmin();
            $john = $this->createSubscriber();
            $this->actingAs($calvin);

            $response = $this->delete("/auth/accounts/$john->ID");

            $response->assertNoContent();
            $this->assertUserDeleted($john);

        }

        /** @test */
        public function admins_cant_delete_their_own_accounts_by_accident () {

            $calvin = $this->createAdmin();
            $this->actingAs($calvin);

            $response = $this->delete("/auth/accounts/$calvin->ID");

            $response->assertForbidden();
            $this->assertUserNotDeleted($calvin);

        }

    }

    class TestCreateAccountViewResponse extends CreateAccountViewResponse
    {

        public function toResponsable() : string
        {

            return "[Test] Create your account. Click here: $this->post_to";
        }

    }

    class TestRegisteredResponse extends RegisteredResponse
    {

        public function toResponsable()
        {

            return "[Test] New User: {$this->user->user_login}";
        }

    }

    class TestCreatesNewUser implements CreatesNewUser
    {


        public function create(Request $request) : int
        {

            return wp_create_user(
                $request->input('user_login'),
                'password',
                $request->input('user_email'),
            );
        }

    }

    class TestDeletesUser implements DeletesUsers {

        public function reassign(int $user_to_be_deleted) : ?int
        {
            return null;
        }

    }