<?php

declare(strict_types=1);

namespace Snicco\Auth;

use Snicco\Session\Session;
use Snicco\View\ViewEngine;
use Snicco\Core\Shared\Encryptor;
use PragmaRX\Google2FA\Google2FA;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Middleware\Secure;
use Snicco\Auth\Fail2Ban\Fail2Ban;
use Snicco\Session\SessionManager;
use Snicco\Core\Application\Config;
use Snicco\Auth\Fail2Ban\Syslogger;
use Snicco\Core\Contracts\MagicLink;
use Snicco\Core\Contracts\Redirector;
use Snicco\Auth\Fail2Ban\PHPSyslogger;
use Snicco\Auth\Middleware\ConfirmAuth;
use Snicco\Auth\Listeners\WPLoginLinks;
use Snicco\Auth\Events\GenerateLoginUrl;
use Snicco\Auth\Responses\LoginRedirect;
use Snicco\Auth\Events\GenerateLogoutUrl;
use Snicco\Auth\Events\SettingAuthCookie;
use Snicco\Core\Contracts\ServiceProvider;
use Snicco\Core\Contracts\ResponseFactory;
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
use Snicco\Mail\Contracts\MailBuilderInterface;
use Snicco\Auth\Contracts\AbstractLoginResponse;
use Snicco\Auth\Listeners\GenerateNewAuthCookie;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Auth\Responses\TwoFactorChallengeView;
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
use Snicco\Auth\Events\FailedTwoFactorAuthentication;
use Snicco\Auth\Events\FailedMagicLinkAuthentication;
use Snicco\Auth\Controllers\AccountAbstractController;
use Snicco\SessionBundle\Events\SessionWasRegenerated;
use Snicco\Auth\Authenticators\MagicLinkAuthenticator;
use Snicco\Auth\Authenticators\TwoFactorAuthenticator;
use Snicco\Auth\Events\FailedPasswordResetLinkRequest;
use Snicco\Auth\Events\FailedLoginLinkCreationRequest;
use Snicco\Auth\Confirmation\TwoFactorAuthConfirmation;
use Snicco\Auth\Contracts\Abstract2FAuthConfirmationView;
use Snicco\Core\Routing\UrlGenerator\InternalUrlGenerator;
use Snicco\Auth\Controllers\AuthSessionAbstractController;
use Snicco\Auth\Authenticators\RedirectIf2FaAuthenticable;
use Snicco\Auth\Contracts\TwoFactorAuthenticationProvider;
use Snicco\Auth\Controllers\RecoveryCodeAbstractController;
use Snicco\SessionBundle\Middleware\StartSessionMiddleware;
use Snicco\Auth\Controllers\ResetPasswordAbstractController;
use Snicco\Auth\Contracts\AbstractEmailAuthConfirmationView;
use Snicco\Auth\Controllers\ForgotPasswordAbstractController;
use Snicco\Auth\Controllers\LoginMagicLinkAbstractController;
use Snicco\Auth\Contracts\AbstractTwoFactorChallengeResponse;
use Snicco\Auth\Controllers\WPLoginRedirectAbstractController;
use Snicco\Auth\Controllers\RegistrationLinkAbstractController;
use Snicco\Auth\Controllers\TwoFactorAuthSetupAbstractController;
use Snicco\Auth\Controllers\ConfirmedAuthSessionAbstractController;
use Snicco\Auth\Controllers\TwoFactorAuthSessionAbstractController;
use Snicco\Auth\Controllers\AuthConfirmationEmailAbstractController;
use Snicco\Core\ExceptionHandling\Exceptions\ConfigurationException;
use Snicco\Auth\Controllers\TwoFactorAuthPreferenceAbstractController;
use Snicco\Auth\Controllers\Compat\PasswordResetEmailAbstractController;
use Snicco\Auth\Controllers\Compat\BulkPasswordResetEmailAbstractController;

class AuthServiceProvider extends ServiceProvider
{
    
    public function bootstrap() :void
    {
        if ( ! $this->config->get('session.enabled')) {
            throw new ConfigurationException(
                'Sessions need to be enabled if you want to use the auth features.'
            );
        }
        
        $this->bindSessionManagerInterface();
    }
    
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
        $this->bindAuthenticators();
        $this->bindAuthenticateSessionMiddleware();
        $this->bindAuthMiddlewares();
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
        add_filter('auth_cookie_expiration', function () {
            return $this->config->get(
                'session.lifetime'
            );
        }, 10, 3);
        
        $this->container->singleton(WPLoginLinks::class, function () {
            return new WPLoginLinks(
                $this->container[InternalUrlGenerator::class]
            );
        });
        
        $this->container->singleton(GenerateNewAuthCookie::class, function () {
            return new GenerateNewAuthCookie(
                $this->container[Session::class]
            );
        });
        
        $this->container->singleton(RefreshAuthCookies::class, function () {
            return new RefreshAuthCookies();
        });
    }
    
    private function bindControllers()
    {
        $this->container->singleton(RegistrationLinkAbstractController::class, function () {
            return new RegistrationLinkAbstractController();
        });
        
        $this->container->singleton(AccountAbstractController::class, function () {
            return new AccountAbstractController($this->container[Dispatcher::class]);
        });
        
        $this->container->singleton(AuthConfirmationEmailAbstractController::class, function () {
            return new AuthConfirmationEmailAbstractController(
                $this->container[MailBuilderInterface::class],
                $this->container[InternalUrlGenerator::class],
            );
        });
        
        $this->container->singleton(AuthSessionAbstractController::class, function () {
            return new AuthSessionAbstractController(
                $this->config->get('auth')
            );
        });
        
        $this->container->singleton(ConfirmedAuthSessionAbstractController::class, function () {
            return new ConfirmedAuthSessionAbstractController(
                $this->container->get(AuthConfirmation::class),
                $this->container->get(Dispatcher::class),
                $this->config->get('auth.confirmation.duration')
            );
        });
        
        $this->container->singleton(ForgotPasswordAbstractController::class, function () {
            return new ForgotPasswordAbstractController(
                $this->container[MailBuilderInterface::class],
                $this->container[Dispatcher::class],
                600
            );
        });
        
        $this->container->singleton(LoginMagicLinkAbstractController::class, function () {
            return new LoginMagicLinkAbstractController(
                $this->container[MailBuilderInterface::class],
                $this->container[Dispatcher::class]
            );
        });
        
        $this->container->singleton(RecoveryCodeAbstractController::class, function () {
            return new RecoveryCodeAbstractController(
                $this->container[Encryptor::class]
            );
        });
        
        $this->container->singleton(ResetPasswordAbstractController::class, function () {
            return new ResetPasswordAbstractController();
        });
        
        $this->container->singleton(TwoFactorAuthPreferenceAbstractController::class, function () {
            return new TwoFactorAuthPreferenceAbstractController(
                $this->container[TwoFactorAuthenticationProvider::class],
                $this->container[Encryptor::class]
            );
        });
        
        $this->container->singleton(TwoFactorAuthSessionAbstractController::class, function () {
            return new TwoFactorAuthSessionAbstractController(
                $this->container[Encryptor::class]
            );
        });
        
        $this->container->singleton(TwoFactorAuthSetupAbstractController::class, function () {
            return new TwoFactorAuthSetupAbstractController(
                $this->container[TwoFactorAuthenticationProvider::class],
                $this->container[Encryptor::class]
            );
        });
        
        $this->container->singleton(WPLoginRedirectAbstractController::class, function () {
            return new WPLoginRedirectAbstractController();
        });
        
        $this->container->singleton(BulkPasswordResetEmailAbstractController::class, function () {
            return new BulkPasswordResetEmailAbstractController(
                $this->container[MailBuilderInterface::class]
            );
        });
        
        $this->container->singleton(PasswordResetEmailAbstractController::class, function () {
            return new PasswordResetEmailAbstractController(
                $this->container[MailBuilderInterface::class]
            );
        });
    }
    
    private function bindAuthConfirmation()
    {
        $this->container->singleton(AuthConfirmation::class, function () {
            $email_confirmation = new EmailAuthConfirmation(
                $this->container[MagicLink::class],
                $this->container[AbstractEmailAuthConfirmationView::class],
                $this->container[InternalUrlGenerator::class]
            );
            
            if ($this->config->get('auth.features.2fa')) {
                return new TwoFactorAuthConfirmation(
                    $email_confirmation,
                    $this->container->get(TwoFactorAuthenticationProvider::class),
                    $this->container->get(ResponseFactory::class),
                    $this->container->get(Encryptor::class),
                    $this->container->get(Abstract2FAuthConfirmationView::class)
                );
            }
            
            return $email_confirmation;
        });
    }
    
    private function bindWpSessionToken()
    {
        add_filter('session_token_manager', function () {
            $manager = $this->container->get(SessionManagerInterface::class);
            
            // Ugly hack. But there is no other way to get this instance to the class that
            // extends WP_SESSION_TOKENS because of WordPress not using interfaces or DI:
            // These globals are immediately unset and can not be used anywhere else.
            global $session_manager;
            $session_manager = $manager;
            
            global $__request;
            $__request = $this->container->get(Request::class);
            
            return WpAuthSessionToken::class;
        });
    }
    
    private function bindAuthSessionManager()
    {
        $this->container->singleton(
            AuthSessionManager::class,
            fn() => new AuthSessionManager(
                $this->container->get(SessionManager::class),
                $this->container->get(SessionDriver::class),
                $this->config->get('auth')
            )
        );
    }
    
    private function bindLoginViewResponse()
    {
        $this->container->singleton(PasswordLoginView::class, function () {
            return new PasswordLoginView(
                $this->container[ViewEngine::class],
                $this->container[InternalUrlGenerator::class],
                $this->container[Config::class]
            );
        });
        $this->container->singleton(MagicLinkLoginView::class, function () {
            return new MagicLinkLoginView(
                $this->container[ViewEngine::class],
                $this->container[InternalUrlGenerator::class],
                $this->container[Config::class]
            );
        });
        
        $this->container->singleton(AbstractLoginView::class, function () {
            $response = $this->config->get('auth.primary_view');
            
            if ( ! $response) {
                $response = $this->config->get('auth.authenticator') === 'email'
                    ? MagicLinkLoginView::class
                    : PasswordLoginView::class;
            }
            
            return $this->container->get($response);
        });
    }
    
    private function bindLoginResponse()
    {
        $this->container->singleton(AbstractLoginResponse::class, function () {
            return new LoginRedirect(
                $this->container[Redirector::class],
                $this->container[InternalUrlGenerator::class],
            );
        });
    }
    
    private function bindTwoFactorProvider()
    {
        $this->container->singleton(TwoFactorAuthenticationProvider::class, function () {
            return new Google2FaAuthenticationProvider(
                new Google2FA(),
                $this->container[Encryptor::class]
            );
        });
    }
    
    private function bindTwoFactorChallengeResponse()
    {
        $this->container->singleton(
            AbstractTwoFactorChallengeResponse::class,
            fn() => $this->container->get(Google2FaChallengeResponse::class)
        );
    }
    
    private function bindRegistrationViewResponse()
    {
        $this->container->singleton(AbstractRegistrationView::class, function () {
            return $this->container->get(EmailRegistrationViewView::class);
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
                $this->container->get(Syslogger::class),
                $this->config->get('auth.fail2ban')
            );
        });
    }
    
    private function bindSessionManagerInterface()
    {
        $this->container->singleton(
            SessionManagerInterface::class,
            fn() => $this->container->get(AuthSessionManager::class)
        );
    }
    
    private function bindTwoFactorView()
    {
        $this->container->singleton(Abstract2FaChallengeView::class, function () {
            return new TwoFactorChallengeView(
                $this->container[ViewEngine::class],
                $this->container[InternalUrlGenerator::class],
            );
        });
    }
    
    private function bindAuthConfirmationView()
    {
        $this->container->singleton(Abstract2FAuthConfirmationView::class, function () {
            return new TwoFactorConfirmationView($this->container[ViewEngine::class]);
        });
        
        $this->container->singleton(AbstractEmailAuthConfirmationView::class, function () {
            return new EmailAuthConfirmationView(
                $this->container[ViewEngine::class],
                $this->container[InternalUrlGenerator::class]
            );
        });
    }
    
    private function bindAuthenticators()
    {
        $this->container->singleton(PasswordAuthenticator::class, function () {
            return new PasswordAuthenticator(
                $this->container[Dispatcher::class]
            );
        });
        $this->container->singleton(MagicLinkAuthenticator::class, function () {
            return new MagicLinkAuthenticator(
                $this->container[MagicLink::class],
                $this->container[Dispatcher::class]
            );
        });
        $this->container->singleton(TwoFactorAuthenticator::class, function () {
            return new TwoFactorAuthenticator(
                $this->container[TwoFactorAuthenticationProvider::class],
                $this->container[Encryptor::class],
                $this->container[Dispatcher::class]
            );
        });
        $this->container->singleton(RedirectIf2FaAuthenticable::class, function () {
            return new RedirectIf2FaAuthenticable(
                $this->container[AbstractTwoFactorChallengeResponse::class],
                $this->container[Encryptor::class]
            );
        });
    }
    
    private function bindAuthenticateSessionMiddleware()
    {
        $this->container->singleton(AuthenticateSession::class, function () {
            return new AuthenticateSession(
                $this->container[SessionManagerInterface::class],
                $this->container[Dispatcher::class]
            );
        });
    }
    
    private function bindAuthMiddlewares()
    {
        $this->container->singleton(AuthUnconfirmed::class, function () {
            return new AuthUnconfirmed(
                $this->container[InternalUrlGenerator::class]
            );
        });
    }
    
}