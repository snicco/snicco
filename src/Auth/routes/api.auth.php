<?php


    declare(strict_types = 1);

    use WPEmerge\Auth\Controllers\AuthSessionController;
    use WPEmerge\Auth\Controllers\AuthConfirmationController;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\LoginMagicLinkController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Routing\Router;
    use WPEmerge\Auth\Controllers\ConfirmAuthMagicLinkController;

    /** @var Router $router */

    $router->middleware('secure')->group(function (Router $router) {

        // Login
        $router->get('/login', [AuthSessionController::class, 'create'])
               ->middleware('guest')
               ->name('login');

        $router->post('/login', [AuthSessionController::class, 'store'])
               ->middleware(['csrf', 'guest'])
               ->name('login');

        // login magic link creation
        $router->post('login/create-magic-link', [LoginMagicLinkController::class, 'store'])
               ->middleware('guest')->name('login.create-magic-link');

        $router->get('login/magic-link', [AuthSessionController::class, 'store'])
               ->middleware('guest')
               ->name('login.magic-link');

        // Logout
        $router->get('/logout/{user_id}', [AuthSessionController::class, 'destroy'])
               ->middleware('signed:absolute')
               ->name('logout')
               ->andNumber('user_id');


        if( AUTH_ALLOW_PW_RESETS ) {

            // forgot-password
            $router->get('/forgot-password', [ForgotPasswordController::class, 'create'])
                   ->middleware('guest')
                   ->name('forgot.password');

            $router->post('/forgot-password', [ForgotPasswordController::class, 'store'])
                   ->middleware(['csrf', 'guest'])
                   ->name('forgot.password');

            // reset-password
            $router->get('/reset-password', [ResetPasswordController::class, 'create'])
                   ->middleware('signed:absolute')
                   ->name('reset.password')
                   ->andNumber('user_id');

            $router->post('/reset-password', [ResetPasswordController::class, 'update'])
                   ->middleware(['csrf', 'signed:absolute'])
                   ->name('reset.password');

        }

        // Auth Confirmation
        $router->get('confirm', [AuthConfirmationController::class, 'create'])->middleware([
            'auth', 'auth.unconfirmed',
        ])->name('confirm.show');

        $router->post('confirm', [AuthConfirmationController::class, 'send'])->middleware([
            'auth', 'csrf', 'auth.unconfirmed',
        ])->name('confirm.send');

        $router->get('confirm/{user_id}', [ConfirmAuthMagicLinkController::class, 'store'])
               ->middleware(['auth', 'signed:absolute', 'auth.unconfirmed'])
               ->name('confirm.store');


    });











