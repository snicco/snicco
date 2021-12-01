<?php

declare(strict_types=1);

namespace Snicco\Auth;

use Snicco\Shared\Encryptor;
use Snicco\Http\Psr7\Request;
use Snicco\Middleware\Secure;
use Snicco\Http\ResponseFactory;
use Snicco\Auth\Fail2Ban\Fail2Ban;
use Snicco\Session\SessionManager;
use Snicco\Auth\Fail2Ban\Syslogger;
use Snicco\Contracts\ServiceProvider;
use Snicco\Auth\Fail2Ban\PHPSyslogger;
use Snicco\Auth\Middleware\ConfirmAuth;
use Snicco\Auth\Listeners\WPLoginLinks;
use Snicco\Auth\Events\GenerateLoginUrl;
use Snicco\Auth\Responses\LoginRedirect;
use Snicco\Auth\Events\GenerateLogoutUrl;
use Snicco\Auth\Events\SettingAuthCookie;
use Snicco\Auth\Contracts\AuthConfirmation;
use Snicco\Auth\Middleware\AuthUnconfirmed;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Auth\Middleware\TwoFactorEnabled;
use Snicco\Auth\Contracts\AbstractLoginView;
use Snicco\Auth\Responses\PasswordLoginView;
use Snicco\Auth\Middleware\TwoFactorDisbaled;
use Snicco\Auth\Listeners\RefreshAuthCookies;
use Snicco\Auth\Responses\MagicLinkLoginView;
use Snicco\Auth\Events\FailedAuthConfirmation;
use Snicco\Auth\Middleware\AuthenticateSession;
use Snicco\Session\Events\SessionWasRegenerated;
use Snicco\Auth\Contracts\AbstractLoginResponse;
use Snicco\Auth\Listeners\GenerateNewAuthCookie;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Auth\Responses\TwoFactorChallengeView;
use Snicco\Auth\Controllers\AuthSessionController;
use Snicco\Auth\Confirmation\EmailAuthConfirmation;
use Snicco\Auth\Contracts\AbstractRegistrationView;
use Snicco\Auth\Contracts\Abstract2FAChallengeView;
use Snicco\Auth\Events\FailedPasswordAuthentication;
use Snicco\Auth\Responses\EmailAuthConfirmationVIew;
use Snicco\Auth\Responses\EmailRegistrationViewView;
use Snicco\Auth\Responses\TwoFactorConfirmationView;
use Snicco\Auth\Authenticators\PasswordAuthenticator;
use Snicco\Auth\Responses\Google2FaChallengeResponse;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\Session\Middleware\StartSessionMiddleware;
use Snicco\Auth\Events\FailedTwoFactorAuthentication;
use Snicco\Auth\Events\FailedMagicLinkAuthentication;
use Snicco\Auth\Authenticators\MagicLinkAuthenticator;
use Snicco\Auth\Authenticators\TwoFactorAuthenticator;
use Snicco\Auth\Events\FailedPasswordResetLinkRequest;
use Snicco\Auth\Events\FailedLoginLinkCreationRequest;
use Snicco\Auth\Confirmation\TwoFactorAuthConfirmation;
use Snicco\Auth\Contracts\Abstract2FAuthConfirmationView;
use Snicco\Auth\Authenticators\RedirectIf2FaAuthenticable;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;
use Snicco\Auth\Controllers\ConfirmedAuthSessionController;
use Snicco\Auth\Contracts\AbstractEmailAuthConfirmationView;
use Snicco\Auth\Contracts\AbstractTwoFactorChallengeResponse;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class AuthServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        $this->bindAuthPipeline();
        $this->extendRoutes(dirname(__DIR__).DIRECTORY_SEPARATOR.'routes');
        $this->extendViews(dirname(__DIR__).DIRECTORY_SEPARATOR.'views');
        
        $this->bindEvents();
        $this->bindControllers();
        $this->bindAuthConfirmation();
        $this->bindWpSessionToken();
        $this->bindAuthSessionManager();
        $this->bindLoginViewResponse();
        $this->bindLoginResponse();
        $this->bindTwoFactorProvider();
        $this->bindTwoFactorChallengeResponse();
        $this->bindTwoFactorView();
        $this->bindAuthConfirmationView();
        $this->bindRegistrationViewResponse();
        $this->bindFail2Ban();
    }
    
    public function bootstrap() :void
    {
        if ( ! $this->config->get('session.enabled')) {
            throw new ConfigurationException(
                'Sessions need to be enabled if you want to use the auth features.'
            );
        }
        
        $this->bindSessionManagerInterface();
    }
    
    private function bindConfig()
    {
        // Authentication
        $this->config->extend('auth.confirmation.duration', SessionManager::HOUR_IN_SEC * 3);
        $this->config->extend('auth.idle', SessionManager::HOUR_IN_SEC / 2);
        $this->config->extend('auth.authenticator', 'password');
        $this->config->extend('auth.features.remember_me', false);
        $this->config->extend('auth.features.password-resets', false);
        $this->config->extend('auth.features.2fa', false);
        $this->config->extend('auth.features.registration', false);
        
        // Middleware
        $this->config->extend('middleware.aliases', [
            'auth.confirmed' => ConfirmAuth::class,
            'auth.unconfirmed' => AuthUnconfirmed::class,
            '2fa.disabled' => TwoFactorDisbaled::class,
            '2fa.enabled' => TwoFactorEnabled::class,
        ]);
        $this->config->extend('middleware.groups.global', [
            Secure::class,
            StartSessionMiddleware::class,
            AuthenticateSession::class,
        ]);
        $this->config->extend('middleware.priority', [
            StartSessionMiddleware::class,
            AuthenticateSession::class,
        ]);
        
        // Endpoints
        $this->config->extend('auth.endpoints.prefix', 'auth');
        $this->config->extend('auth.endpoints.login', 'login');
        $this->config->extend('auth.endpoints.magic-link', 'magic-link');
        $this->config->extend('auth.endpoints.confirm', 'confirm');
        $this->config->extend('auth.endpoints.2fa', 'two-factor');
        $this->config->extend('auth.endpoints.challenge', 'challenge');
        $this->config->extend('auth.endpoints.register', 'register');
        $this->config->extend('auth.endpoints.forgot-password', 'forgot-password');
        $this->config->extend('auth.endpoints.reset-password', 'reset-password');
        $this->config->extend('auth.endpoints.accounts', 'accounts');
        $this->config->extend('auth.endpoints.accounts_create', 'create');
        $this->config->extend('routing.api.endpoints', [
            
            // Needed so we can respond to requests to wp-login.php and redirect them appropriately.
            // Having /wp-login.php marked as an endpoint will ensure that we boot the Kernel
            // and route pipeline on the init hook which is the only way to intercept and redirect.
            'wp-login.php' => '/wp-login.php',
        
        ]);
        
        $this->config->extend(
            'routing.presets.auth',
            [
                'prefix' => $this->config->get('auth.endpoints.prefix'),
                'name' => 'auth',
            ]
        );
    }
    
    private function bindAuthPipeline()
    {
        $pipeline = $this->config->get('auth.through', []);
        
        if ( ! count($pipeline)) {
            $primary = ($this->config->get('auth.authenticator') === 'email')
                ? MagicLinkAuthenticator::class
                : PasswordAuthenticator::class;
            
            $two_factor = $this->config->get('auth.features.2fa');
            
            $this->config->set(
                'auth.through',
                array_values(
                    array_filter([
                        
                        $two_factor ? TwoFactorAuthenticator::class : null,
                        $two_factor ? RedirectIf2FaAuthenticable::class : null,
                        $primary,
                    
                    ])
                )
            );
        }
    }
    
    private function bindEvents()
    {
        $this->config->extend('events.listeners', [
            GenerateLoginUrl::class => [
                [WPLoginLinks::class, 'createLoginUrl'],
            ],
            GenerateLogoutUrl::class => [
                [WPLoginLinks::class, 'createLogoutUrl'],
            ],
            SettingAuthCookie::class => [
                GenerateNewAuthCookie::class,
            ],
            SessionWasRegenerated::class => [
                RefreshAuthCookies::class,
            ],
        ]);
        
        $this->config->extend('events.mapped', [
            
            'auth_cookie' => [
                [SettingAuthCookie::class, 999],
            ],
        
        ]);
        
        $this->config->extend('events.last', [
            'login_url' => GenerateLoginUrl::class,
            'logout_url' => GenerateLogoutUrl::class,
        ]);
        
        // This filter is very misleading from WordPress. It does not filter the expiry value in
        // "setcookie()" function, but filters the expiration fragment in the cookie hash.
        // The maximum value can only ever be our session absolute timeout.
        add_filter('auth_cookie_expiration', fn() => $this->config->get('session.lifetime'), 10, 3);
    }
    
    private function bindControllers()
    {
        $this->container->singleton(ConfirmedAuthSessionController::class, function () {
            return new ConfirmedAuthSessionController(
                $this->container->make(AuthConfirmation::class),
                $this->container->make(Dispatcher::class),
                $this->config->get('auth.confirmation.duration')
            );
        });
        
        $this->container->singleton(
            AuthSessionController::class, function () {
            return new AuthSessionController(
                $this->config->get('auth')
            );
        }
        );
    }
    
    private function bindAuthConfirmation()
    {
        $this->container->singleton(AuthConfirmation::class, function () {
            if ($this->config->get('auth.features.2fa')) {
                return new TwoFactorAuthConfirmation(
                    $this->container->make(EmailAuthConfirmation::class),
                    $this->container->make(TwoFactorAuthenticationProvider::class),
                    $this->container->make(ResponseFactory::class),
                    $this->container->make(Encryptor::class),
                    $this->container->make(Abstract2FAuthConfirmationView::class)
                );
            }
            
            return $this->container->make(EmailAuthConfirmation::class);
        });
    }
    
    private function bindWpSessionToken()
    {
        add_filter('session_token_manager', function () {
            $manager = $this->container->make(SessionManagerInterface::class);
            
            // Ugly hack. But there is no other way to get this instance to the class that
            // extends WP_SESSION_TOKENS because of WordPress not using interfaces or DI:
            // These globals are immediately unset and can not be used anywhere else.
            global $session_manager;
            $session_manager = $manager;
            
            global $__request;
            $__request = $this->container->make(Request::class);
            
            return WpAuthSessionToken::class;
        });
    }
    
    private function bindAuthSessionManager()
    {
        $this->container->singleton(
            AuthSessionManager::class,
            fn() => new AuthSessionManager(
                $this->container->make(SessionManager::class),
                $this->container->make(SessionDriver::class),
                $this->config->get('auth')
            )
        );
    }
    
    private function bindLoginViewResponse()
    {
        $this->container->singleton(AbstractLoginView::class, function () {
            $response = $this->config->get('auth.primary_view');
            
            if ( ! $response) {
                $response =
                    $this->config->get('auth.authenticator') === 'email'
                        ? MagicLinkLoginView::class
                        : PasswordLoginView::class;
            }
            
            return $this->container->make($response);
        });
    }
    
    private function bindLoginResponse()
    {
        $this->container->singleton(
            AbstractLoginResponse::class,
            fn() => $this->container->make(LoginRedirect::class)
        );
    }
    
    private function bindTwoFactorProvider()
    {
        $this->container->singleton(
            TwoFactorAuthenticationProvider::class,
            fn() => $this->container->make(Google2FaAuthenticationProvider::class)
        );
    }
    
    private function bindTwoFactorChallengeResponse()
    {
        $this->container->singleton(AbstractTwoFactorChallengeResponse::class,
            fn() => $this->container->make(Google2FaChallengeResponse::class)
        );
    }
    
    private function bindRegistrationViewResponse()
    {
        $this->container->singleton(AbstractRegistrationView::class, function () {
            return $this->container->make(EmailRegistrationViewView::class);
        });
    }
    
    private function bindFail2Ban()
    {
        if ( ! $this->config->get('auth.fail2ban.enabled')) {
            return;
        }
        
        $this->config->extend('auth.fail2ban.daemon', 'sniccowp');
        $this->config->extend('auth.fail2ban.facility', LOG_AUTH);
        $this->config->extend('auth.fail2ban.flags', LOG_NDELAY | LOG_PID);
        $this->config->extend('events.listeners', [
            
            FailedPasswordResetLinkRequest::class => [
                [Fail2Ban::class, 'report'],
            ],
            
            FailedMagicLinkAuthentication::class => [
                [Fail2Ban::class, 'report'],
            ],
            
            FailedPasswordAuthentication::class => [
                [Fail2Ban::class, 'report'],
            ],
            
            FailedTwoFactorAuthentication::class => [
                [Fail2Ban::class, 'report'],
            ],
            
            FailedLoginLinkCreationRequest::class => [
                [Fail2Ban::class, 'report'],
            ],
            
            FailedAuthConfirmation::class => [
                [Fail2Ban::class, 'report'],
            ],
        ]);
        
        $this->container->singleton(Syslogger::class, fn() => new PHPSyslogger());
        $this->container->singleton(Fail2Ban::class, function () {
            return new Fail2Ban(
                $this->container->make(Syslogger::class),
                $this->config->get('auth.fail2ban')
            );
        });
    }
    
    private function bindSessionManagerInterface()
    {
        $this->container->singleton(
            SessionManagerInterface::class,
            fn() => $this->container->make(AuthSessionManager::class)
        );
    }
    
    private function bindTwoFactorView()
    {
        $this->container->singleton(Abstract2FaChallengeView::class,
            fn() => $this->container->make(TwoFactorChallengeView::class)
        );
    }
    
    private function bindAuthConfirmationView()
    {
        $this->container->singleton(
            Abstract2FAuthConfirmationView::class,
            fn() => $this->container->make(TwoFactorConfirmationView::class)
        );
        
        $this->container->singleton(AbstractEmailAuthConfirmationView::class,
            fn() => $this->container->make(EmailAuthConfirmationView::class)
        );
    }
    
}