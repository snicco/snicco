<?php


    declare(strict_types = 1);

    use BetterWP\Application\ApplicationConfig;
    use BetterWP\Auth\Controllers\AccountController;
    use BetterWP\Auth\Controllers\AuthConfirmationEmailController;
    use BetterWP\Auth\Controllers\AuthSessionController;
    use BetterWP\Auth\Controllers\ConfirmedAuthSessionController;
    use BetterWP\Auth\Controllers\ForgotPasswordController;
    use BetterWP\Auth\Controllers\LoginMagicLinkController;
    use BetterWP\Auth\Controllers\RecoveryCodeController;
    use BetterWP\Auth\Controllers\RegistrationLinkController;
    use BetterWP\Auth\Controllers\ResetPasswordController;
    use BetterWP\Auth\Controllers\TwoFactorAuthSessionController;
    use BetterWP\Auth\Controllers\TwoFactorAuthPreferenceController;
    use BetterWP\Routing\Router;

    /** @var Router $router */
    /** @var ApplicationConfig $config */

    // Login
    $router->middleware('guest')->group(function (Router $router) use ($config) {


        $router->get('/login', [AuthSessionController::class, 'create'])
               ->name('login');

        $router->post('/login', [AuthSessionController::class, 'store'])
               ->middleware('csrf');

        // Magic-link
        if ($config->get('auth.authenticator') === 'email') {

            $router->post('login/create-magic-link', [LoginMagicLinkController::class, 'store'])
                   ->middleware('csrf')
                   ->name('login.create-magic-link');

            $router->get('login/magic-link', [AuthSessionController::class, 'store'])
                   ->name('login.magic-link');

        }


    });

    // Logout
    $router->get('/logout/{user_id}', [AuthSessionController::class, 'destroy'])
           ->middleware('signed:absolute')
           ->name('logout')
           ->andNumber('user_id');

    // Auth Confirmation
    $router->middleware(['auth', 'auth.unconfirmed'])->group(function (Router $router) {

        $router->get('confirm', [ConfirmedAuthSessionController::class, 'create'])->name('confirm');

        $router->post('confirm', [ConfirmedAuthSessionController::class, 'store'])
               ->middleware('csrf');

        $router->get('confirm/magic-link', [ConfirmedAuthSessionController::class, 'store'])
               ->name('confirm.magic-link');

        $router->post('/confirm/email', [AuthConfirmationEmailController::class, 'store'])
               ->middleware('csrf')
               ->name('confirm.email');

    });

    // 2FA
    if ($config->get('auth.features.2fa')) {

        $router->post('two-factor/preferences', [TwoFactorAuthPreferenceController::class, 'store'])
               ->middleware(['auth', 'auth.confirmed'])
               ->name('two-factor.preferences');

        $router->delete('two-factor/preferences', [
            TwoFactorAuthPreferenceController::class, 'destroy',
        ])
               ->middleware(['auth', 'auth.confirmed']);

        $router->get('two-factor/challenge', [TwoFactorAuthSessionController::class, 'create'])
               ->name('2fa.challenge');

        // recovery codes.
        $router->name('2fa.recovery-codes')->middleware(['auth', 'auth.confirmed'])
               ->group(function (Router $router) {

                   $router->get('two-factor/recovery-codes', [
                       RecoveryCodeController::class, 'index',
                   ]);

                   $router->put('two-factor/recovery-codes', [
                       RecoveryCodeController::class, 'update',
                   ])->middleware('csrf:persist');


               });


    }

    // password resets
    if ($config->get('auth.features.password-resets')) {

        // forgot-password
        $router->get('/forgot-password', [ForgotPasswordController::class, 'create'])
               ->middleware('guest')
               ->name('forgot.password');

        $router->post('/forgot-password', [ForgotPasswordController::class, 'store'])
               ->middleware(['csrf', 'guest']);

        // reset-password
        $router->get('/reset-password', [ResetPasswordController::class, 'create'])
               ->middleware('signed:absolute')
               ->name('reset.password');

        $router->put('/reset-password', [ResetPasswordController::class, 'update'])
               ->middleware(['csrf', 'signed:absolute']);

    }

    // registration
    if ($config->get('auth.features.registration')) {

        $router->middleware('guest')->group(function ($router) {

            $router->get('register', [RegistrationLinkController::class, 'create'])
                   ->name('register');

            $router->post('register', [RegistrationLinkController::class, 'store']);

        });

        $router->name('accounts')->group(function (Router $router) {

            $router->get('/accounts/create', [AccountController::class, 'create'])
                   ->middleware(['guest', 'signed:absolute'])
                   ->name('create');

            $router->post('/accounts', [AccountController::class, 'store'])
                   ->middleware(['guest', 'csrf', 'signed'])
                   ->name('store');

            $router->delete('/accounts/{user_id}', [AccountController::class, 'destroy'])
                   ->middleware(['auth', 'csrf'])
                   ->andNumber('user_id');

        });





    }










