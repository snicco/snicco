<?php


    declare(strict_types = 1);

    use WPEmerge\Routing\Router;
    use WPEmerge\Session\Controllers\ConfirmAuthController;
    use WPEmerge\Session\Controllers\MagicLinkLoginController;
    use WPEmerge\Session\Controllers\WpLoginSessionController;

    /** @var Router $router */

    $router->prefix('auth')->name('auth.confirm')->group(function ($router) {

        $router->middleware(['auth', 'auth.unconfirmed'])->group(function ($router)  {

            $router->get('confirm', [ConfirmAuthController::class, 'show'])
                   ->name('show');

            $router->post('confirm', [ConfirmAuthController::class, 'send'])
                   ->name('send')
                   ->middleware('csrf');

        });

        $router->get('confirm/{user_id}', [MagicLinkLoginController::class, 'create'])
               ->name('magic-login')
               ->middleware(['validSignature', 'auth.unconfirmed']);

    });

    $router->post('/wp-login.php', [WpLoginSessionController::class, 'create']);

    $router->get('/wp-login.php', [WpLoginSessionController::class, 'destroy']);