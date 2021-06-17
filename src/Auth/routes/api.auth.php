<?php


    declare(strict_types = 1);

    use WPEmerge\Auth\Controllers\AuthController;
    use WPEmerge\Auth\Controllers\AuthConfirmationController;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Routing\Router;
    use WPEmerge\Auth\Controllers\ConfirmAuthMagicLinkController;

    /** @var Router $router */

    $router->middleware('secure')->group(function (Router $router)  {

        $router->get('/login', [AuthController::class, 'create'])
               ->middleware('guest')
               ->name('login');

        $router->post('/login', [AuthController::class, 'store'])
               ->middleware(['csrf', 'guest'])
               ->name('login');

        $router->get('/logout/{user_id}', [AuthController::class, 'destroy'])
               ->middleware('signed:absolute')
               ->name('logout')
               ->andNumber('user_id');

        $router->get('/forgot-password', [ForgotPasswordController::class, 'create'])
               ->middleware('guest')
               ->name('forgot.password');

        $router->post('/forgot-password', [ForgotPasswordController::class, 'store'])
               ->middleware(['csrf', 'guest'])
               ->name('forgot.password');

        $router->get('/reset-password', [ResetPasswordController::class, 'create'])
               ->middleware('signed:absolute')
               ->name('reset.password')
               ->andNumber('user_id');

        $router->post('/reset-password', [ResetPasswordController::class, 'update'])
               ->middleware('csrf')
               ->name('reset.password');

        $router->get('/password-reset/success', [ResetPasswordController::class, 'show'])
               ->name('reset.password.show');


        $router->get('confirm', [AuthConfirmationController::class, 'create'])->middleware(['auth','auth.unconfirmed'])->name('confirm.show');

        $router->post('confirm', [AuthConfirmationController::class, 'send'])->middleware(['auth','csrf', 'auth.unconfirmed'])->name('confirm.send');

        $router->get('confirm/{user_id}', [ConfirmAuthMagicLinkController::class, 'store'])
               ->middleware(['auth', 'signed:absolute', 'auth.unconfirmed'])
               ->name('confirm.store');

    });











