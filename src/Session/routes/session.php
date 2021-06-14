<?php


    declare(strict_types = 1);

    use WPEmerge\Routing\Router;
    use WPEmerge\Session\Controllers\ConfirmAuthController;
    use WPEmerge\Session\Controllers\ConfirmAuthMagicLinkController;

    /** @var Router $router */

    $router->prefix('auth')->name('auth.confirm')
                           ->middleware('secure')
                           ->group(function ($router) {

        $router->middleware(['auth', 'auth.unconfirmed'])->group(function ($router)  {

            $router->get('confirm', [ConfirmAuthController::class, 'show'])
                   ->name('show');

            $router->post('confirm', [ConfirmAuthController::class, 'send'])
                   ->name('send')
                   ->middleware('csrf');

        });

        $router->get('confirm/{user_id}', [ConfirmAuthMagicLinkController::class, 'create'])
               ->name('magic-login')
               ->middleware(['signed', 'auth.unconfirmed']);

    });

