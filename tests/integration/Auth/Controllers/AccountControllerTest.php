<?php

declare(strict_types=1);

namespace Tests\integration\Auth\Controllers;

use WP_User;
use Tests\AuthTestCase;
use Tests\stubs\TestApp;
use Snicco\Events\Event;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;
use Snicco\Auth\Events\Registration;
use Snicco\Auth\Contracts\DeletesUsers;
use Snicco\Auth\Contracts\CreatesNewUser;
use Snicco\Contracts\ResponseableInterface;
use Snicco\Auth\Contracts\CreateAccountView;
use Snicco\Auth\Contracts\AbstractRegistrationResponse;

class AccountControllerTest extends AuthTestCase
{
    
    /** @test */
    public function the_endpoint_cant_be_accessed_if_registration_is_disabled()
    {
        
        $this->withOutConfig('auth.features.registration')->bootApp();
        
        $response = $this->get('/auth/accounts/create');
        $response->assertDelegatedToWordPress();
        
    }
    
    /** @test */
    public function the_endpoint_cant_be_accessed_authenticated()
    {
        
        $this->actingAs($this->createAdmin())->bootApp();
        
        $response = $this->get('/auth/accounts/create');
        $response->assertRedirectToRoute('dashboard');
        
    }
    
    /** @test */
    public function the_create_account_view_can_be_rendered()
    {
        
        $this->bootApp();
        
        $response = $this->get($this->validCreateLink());
        
        $response->assertOk()->assertSee('[Test] Create your account.');
        
        $store_link = $this->validStoreLink();
        $response->assertOk()->assertSee("Click here: $store_link", false);
        
    }
    
    /** @test */
    public function an_account_can_be_created()
    {
        
        $this->bootApp();
        
        Event::fake();
        
        $response = $this->post($this->validStoreLink(), [
            'user_login' => 'calvin',
            'user_email' => 'c@web.de',
        ]);
        
        $response->assertOk()->assertSee('[Test] New User: calvin');
        
        $user = get_user_by('login', 'calvin');
        $this->assertInstanceOf(WP_User::class, $user);
        
        Event::assertDispatched(function (Registration $event) use ($user) {
            
            return $event->user->user_login === $user->user_login
                   && $event->user->user_login
                      === 'calvin';
        });
        
    }
    
    /** @test */
    public function an_account_can_be_deleted_from_the_same_user()
    {
        
        $this->bootApp();
        
        $calvin = $this->createSubscriber();
        $this->actingAs($calvin);
        
        $this->withHeader('Accept', 'application/json');
        $response = $this->delete("/auth/accounts/$calvin->ID");
        
        $response->assertStatus(204);
        $this->assertUserDeleted($calvin);
        
    }
    
    /** @test */
    public function a_user_can_only_delete_his_own_account()
    {
        
        $this->bootApp();
        
        $calvin = $this->createSubscriber();
        $john = $this->createSubscriber();
        $this->actingAs($calvin);
        
        $this->withHeader('Accept', 'application/json');
        $response = $this->delete("/auth/accounts/$john->ID");
        
        $response->assertForbidden();
        $this->assertUserNotDeleted($john);
        
    }
    
    /** @test */
    public function only_allowed_user_roles_can_delete_their_own_accounts()
    {
        
        $this->bootApp();
        
        // In our test only subscribers can delete their own account
        $calvin = $this->createAuthor();
        $this->actingAs($calvin);
        
        $this->withHeader('Accept', 'application/json');
        $response = $this->delete("/auth/accounts/$calvin->ID");
        
        $response->assertForbidden();
        $this->assertUserNotDeleted($calvin);
        
    }
    
    /** @test */
    public function admins_can_delete_accounts_for_other_users_regardless_of_the_whitelist()
    {
        
        $this->bootApp();
        
        $calvin = $this->createAdmin();
        $john = $this->createEditor();
        $this->actingAs($calvin);
        
        $this->withHeader('Accept', 'application/json');
        $response = $this->delete("/auth/accounts/$john->ID");
        
        $response->assertNoContent();
        $this->assertUserDeleted($john);
        
    }
    
    /** @test */
    public function admins_cant_delete_their_own_accounts_by_accident()
    {
        
        $this->bootApp();
        
        $calvin = $this->createAdmin();
        $this->actingAs($calvin);
        
        $this->withHeader('Accept', 'application/json');
        $response = $this->delete("/auth/accounts/$calvin->ID");
        
        $response->assertForbidden();
        $this->assertUserNotDeleted($calvin);
        
    }
    
    /** @test */
    public function admins_cant_delete_accounts_for_other_admins()
    {
        
        $this->bootApp();
        
        $calvin = $this->createAdmin();
        $john = $this->createAdmin();
        $this->actingAs($calvin);
        
        $this->withHeader('Accept', 'application/json');
        $response = $this->delete("/auth/accounts/$john->ID");
        
        $response->assertForbidden();
        $this->assertUserNotDeleted($john);
        
    }
    
    /** @test */
    public function for_non_json_requests_a_custom_response_can_be_provided()
    {
        
        $this->bootApp();
        
        $calvin = $this->createSubscriber();
        $this->actingAs($calvin);
        
        $response = $this->delete("/auth/accounts/$calvin->ID");
        
        $this->assertUserDeleted($calvin);
        $response->assertRedirect('/test/thank-you');
    }
    
    protected function validCreateLink() :string
    {
        
        ;
        
        return TestApp::url()->signedRoute('auth.accounts.create', [], 300, true);
        
    }
    
    protected function validStoreLink() :string
    {
        
        ;
        
        return TestApp::url()->signedRoute('auth.accounts.store', [], 900);
    }
    
    protected function setUp() :void
    {
        
        $this->afterApplicationCreated(function () {
            
            $this->withAddedConfig('auth.features.registration', true);
            
        });
        
        $this->afterApplicationBooted(function () {
            
            $this->withoutMiddleware('csrf');
            $this->instance(CreateAccountView::class, new TestCreateAccountView());
            $this->instance(AbstractRegistrationResponse::class, new TestRegisteredResponse());
            $this->instance(CreatesNewUser::class, new TestCreatesNewUser());
            $this->instance(CreatesNewUser::class, new TestCreatesNewUser());
            $this->instance(DeletesUsers::class, $this->app->resolve(TestDeletesUser::class));
            
        });
        
        parent::setUp();
        
    }
    
}

class TestCreateAccountView extends CreateAccountView
{
    
    public function toResponsable() :string
    {
        
        return "[Test] Create your account. Click here: $this->post_to";
    }
    
}

class TestRegisteredResponse extends AbstractRegistrationResponse
{
    
    public function toResponsable()
    {
        
        return "[Test] New User: {$this->user->user_login}";
    }
    
}

class TestCreatesNewUser implements CreatesNewUser
{
    
    public function create(Request $request) :int
    {
        
        return wp_create_user(
            $request->input('user_login'),
            'password',
            $request->input('user_email'),
        );
    }
    
}

class TestDeletesUser implements DeletesUsers
{
    
    /**
     * @var ResponseFactory
     */
    private $response_factory;
    
    public function __construct(ResponseFactory $response_factory)
    {
        $this->response_factory = $response_factory;
    }
    
    public function reassign(int $user_to_be_deleted) :?int
    {
        
        return null;
    }
    
    public function allowedUserRoles() :array
    {
        
        return [
            'subscriber',
            // 'author'
        ];
    }
    
    public function response() :ResponseableInterface
    {
        return $this->response_factory->redirect()->to('/test/thank-you');
    }
    
}