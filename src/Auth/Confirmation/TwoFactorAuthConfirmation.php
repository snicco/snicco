<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Confirmation;

    use WP_User;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\ResponseFactory;
    use Snicco\Contracts\EncryptorInterface;
    use Snicco\Auth\Contracts\AuthConfirmation;
    use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
    use Snicco\Auth\Traits\PerformsTwoFactorAuthentication;
    use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;

    class TwoFactorAuthConfirmation implements AuthConfirmation
    {
	
	    use PerformsTwoFactorAuthentication;
	    use InteractsWithTwoFactorSecrets;
	
	    private AuthConfirmation $fallback;
	    private WP_User $current_user;
	    private TwoFactorAuthenticationProvider $provider;
	    private ResponseFactory $response_factory;
	    private EncryptorInterface $encryptor;
	
	    private string $user_secret;
	
	    public function __construct(
		    AuthConfirmation                $fallback,
		    TwoFactorAuthenticationProvider $provider,
		    ResponseFactory                 $response_factory,
		    EncryptorInterface              $encryptor,
		    WP_User                         $current_user
	    ) {
		    $this->fallback = $fallback;
		    $this->current_user = $current_user;
		    $this->encryptor = $encryptor;
		    $this->user_secret = $this->twoFactorSecret($this->current_user->ID);
		    $this->provider = $provider;
		    $this->response_factory = $response_factory;
		
	    }
	
	    public function confirm(Request $request)
	    {
		
		    if ( ! $this->userHasTwoFactorEnabled($request->user()) ) {
			    return $this->fallback->confirm($request);
		    }
		
		    $valid = $this->validateTwoFactorAuthentication($this->provider, $request, $request->userId());
		
		    if ( $valid === true ) {
			    return true;
		    }
		
		    return ['message' => 'Invalid code provided.'];
		
	    }
	
	    public function viewResponse(Request $request)
	    {
		
		    if ( ! $this->userHasTwoFactorEnabled($request->user()) ) {
			
			    return $this->fallback->viewResponse($request);
			
		    }
		
		    return $this->response_factory->view('auth-layout', [
			    'view' => 'auth-two-factor-challenge',
			    'post_to' => $request->path(),
		    ]);
		
	    }
	
    }