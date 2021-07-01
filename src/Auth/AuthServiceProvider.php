<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth;

    use WPEmerge\Auth\Authenticators\MagicLinkAuthenticator;
    use WPEmerge\Auth\Authenticators\PasswordAuthenticator;
    use WPEmerge\Auth\Authenticators\RedirectIf2FaAuthenticable;
    use WPEmerge\Auth\Authenticators\TwoFactorAuthenticator;
    use WPEmerge\Auth\Confirmation\EmailAuthConfirmation;
    use WPEmerge\Auth\Confirmation\TwoFactorAuthConfirmation;
    use WPEmerge\Auth\Contracts\AuthConfirmation;
    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\Controllers\AuthSessionController;
    use WPEmerge\Auth\Controllers\ConfirmedAuthSessionController;
    use WPEmerge\Auth\Events\GenerateLoginUrl;
    use WPEmerge\Auth\Events\GenerateLogoutUrl;
    use WPEmerge\Auth\Events\SettingAuthCookie;
    use WPEmerge\Auth\Listeners\GenerateNewAuthCookie;
    use WPEmerge\Auth\Listeners\RefreshAuthCookies;
    use WPEmerge\Auth\Listeners\WpLoginLinkGenerator;
    use WPEmerge\Auth\Middleware\AuthenticateSession;
    use WPEmerge\Auth\Middleware\AuthUnconfirmed;
    use WPEmerge\Auth\Middleware\ConfirmAuth;
    use WPEmerge\Auth\Responses\EmailRegistrationViewResponse;
    use WPEmerge\Auth\Responses\Google2FaChallengeResponse;
    use WPEmerge\Auth\Contracts\LoginResponse;
    use WPEmerge\Auth\Contracts\LoginViewResponse;
    use WPEmerge\Auth\Responses\PasswordLoginView;
    use WPEmerge\Auth\Responses\RedirectToDashboardResponse;
    use WPEmerge\Auth\Contracts\RegistrationViewResponse;
    use WPEmerge\Auth\Contracts\TwoFactorChallengeResponse;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\WpInit;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Middleware\Secure;
    use WPEmerge\Session\Events\SessionRegenerated;
    use WPEmerge\Session\Contracts\SessionDriver;
    use WPEmerge\Session\Middleware\StartSessionMiddleware;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Session\Contracts\SessionManagerInterface;

    class AuthServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindConfig();

            $this->bindAuthPipeline();

            $this->extendRoutes(__DIR__.DIRECTORY_SEPARATOR.'routes');

            $this->extendViews(__DIR__.DIRECTORY_SEPARATOR.'views');

            $this->bindEvents();

            $this->bindControllers();

            $this->bindAuthConfirmation();

            $this->bindWpSessionToken();

            $this->bindAuthSessionManager();

            $this->bindLoginViewResponse();

            $this->bindLoginResponse();

            $this->bindTwoFactorProvider();

            $this->bindTwoFactorChallengeResponse();

            $this->bindRegistrationViewResponse();

        }

        public function bootstrap() : void
        {

            $this->bindSessionManagerInterface();

            $this->updateSessionLifetime();


        }

        private function bindEvents()
        {

            $this->config->extend('events.listeners', [
                GenerateLoginUrl::class => [

                    [WpLoginLinkGenerator::class, 'loginUrl'],

                ],
                GenerateLogoutUrl::class => [
                    [WpLoginLinkGenerator::class, 'logoutUrl'],
                ],
                SettingAuthCookie::class => [
                    GenerateNewAuthCookie::class,
                ],
                SessionRegenerated::class => [
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

            // This filter is very misleading from WordPress. It does not filter the expire value in
            // "setcookie()" function, but filters the expiration fragment in the cookie hash.
            add_filter('auth_cookie_expiration', function () {

                return $this->config->get('auth.features.remember_me');

            }, 10, 3);


        }

        private function bindControllers()
        {

            $this->container->singleton(ConfirmedAuthSessionController::class, function () {
                return new ConfirmedAuthSessionController(
                    $this->container->make(AuthConfirmation::class),
                    $this->config->get('auth.confirmation.duration')
                );
            });

            $this->container->singleton(AuthSessionController::class, function () {

                return new AuthSessionController(
                    $this->config->get('auth')
                );

            });

        }

        private function bindConfig()
        {

            $this->config->extend('auth.confirmation.duration', SessionManager::HOUR_IN_SEC * 3);
            $this->config->extend('auth.idle', SessionManager::HOUR_IN_SEC / 2);
            $this->config->extend('auth.endpoint', 'auth');
            $this->config->extend('routing.api.endpoints', [

                'auth' => $this->config->get('auth.endpoint',),

            ]);
            $this->config->extend('auth.features.remember_me', false);
            $this->config->extend('auth.features.password-resets', false);
            $this->config->extend('auth.features.2fa', false);
            $this->config->extend('auth.features.registration', false);

            $this->config->extend('middleware.aliases', [
                'auth.confirmed' => ConfirmAuth::class,
                'auth.unconfirmed' => AuthUnconfirmed::class,
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


        }

        private function bindWpSessionToken()
        {

            add_filter('session_token_manager', function () {

                $manager = $this->container->make(SessionManagerInterface::class);

                // Ugly hack. But there is no other way to get this instance to the class that
                // extends WP_SESSION_TOKENS because of Wordpress not using interfaces or DI:
                // These globals are immediately unset and can not be used anywhere else.
                global $session_manager;
                $session_manager = $manager;

                global $__request;
                $__request = $this->container->make(Request::class);

                return WpAuthSessionToken::class;

            });

        }

        private function updateSessionLifetime()
        {

            $remember = $this->config->get('auth.features.remember_me', false);

            if (is_int($remember) && $remember > 0) {

                $remember_lifetime = $remember;
                $session_lifetime = $this->config->get('session.lifetime');

                $max = max($remember_lifetime, $session_lifetime);

                $this->config->set('auth.features.remember_me', $max);
                $this->config->set('session.lifetime', $max);
                $this->config->set('auth.timeouts.absolute', $max);

            }
            else {

                $this->config->set('auth.features.remember_me', 0);

            }

        }

        private function bindSessionManagerInterface()
        {

            $this->container->singleton(SessionManagerInterface::class, function () {

                return $this->container->make(AuthSessionManager::class);

            });

        }

        private function bindAuthSessionManager()
        {

            $this->container->singleton(AuthSessionManager::class, function () {

                return new AuthSessionManager(
                    $this->container->make(SessionManager::class),
                    $this->container->make(SessionDriver::class),
                    $this->config->get('auth')
                );

            });
        }

        private function bindLoginViewResponse()
        {

            $this->container->singleton(LoginViewResponse::class, function () {

                $response = $this->config->get('auth.primary_view', PasswordLoginView::class);

                return $this->container->make($response);

            });
        }

        private function bindAuthPipeline()
        {

            $pipeline = $this->config->get('auth.through', []);

            if ( ! count($pipeline) ) {

                $primary = ( $this->config->get('auth.authenticator', 'password') === 'email' )
                    ? MagicLinkAuthenticator::class
                    : PasswordAuthenticator::class;

                $two_factor = $this->config->get('auth.features.2fa');

                $this->config->set('auth.through', array_values(array_filter([

                    $two_factor ? TwoFactorAuthenticator::class : null,
                    $two_factor ? RedirectIf2FaAuthenticable::class : null,
                    $primary

                ])));

            }


        }

        private function bindLoginResponse()
        {
            $this->container->singleton(LoginResponse::class, function () {

                return $this->container->make(RedirectToDashboardResponse::class);

            });
        }

        private function bindTwoFactorProvider()
        {
            $this->container->singleton(TwoFactorAuthenticationProvider::class, function () {

                return $this->container->make(Google2FaAuthenticationProvider::class);

            });
        }

        private function bindTwoFactorChallengeResponse()
        {

            $this->container->singleton(TwoFactorChallengeResponse::class, function () {

                return $this->container->make(Google2FaChallengeResponse::class);

            });

        }

        private function bindRegistrationViewResponse()
        {
             $this->container->singleton(RegistrationViewResponse::class, function () {

                return $this->container->make(EmailRegistrationViewResponse::class);

            });
        }

        private function bindAuthConfirmation()
        {
            $this->container->singleton(AuthConfirmation::class, function () {

                if ( $this->config->get('auth.features.2fa') ) {

                    return new TwoFactorAuthConfirmation(
                        $this->container->make(EmailAuthConfirmation::class),
                        $this->container->make(TwoFactorAuthenticationProvider::class),
                        $this->container->make(ResponseFactory::class),
                        $this->container->make(EncryptorInterface::class),
                        WP::currentUser()
                    );

                }

                return $this->container->make(EmailAuthConfirmation::class);

            });
        }


    }