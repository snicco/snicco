<?php


    declare(strict_types = 1);

    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Auth\Controllers\AuthSessionController;
    use WPEmerge\Auth\Controllers\ConfirmedAuthSessionController;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\LoginMagicLinkController;
    use WPEmerge\Auth\Controllers\RecoveryCodeController;
    use WPEmerge\Auth\Controllers\RegistrationLinkController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Auth\Controllers\TwoFactorAuthSessionController;
    use WPEmerge\Auth\Controllers\TwoFactorAuthPreferenceController;
    use WPEmerge\Routing\Router;

    /** @var Router $router */
    /** @var ApplicationConfig $config */

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

    // Auth Confirmation
    $router->get('confirm', [ConfirmedAuthSessionController::class, 'create'])->middleware([
        'auth', 'auth.unconfirmed',
    ])->name('confirm');

    $router->post('confirm', [ConfirmedAuthSessionController::class, 'store'])
           ->middleware(['auth', 'auth.unconfirmed', 'csrf']);

    $router->delete('confirm', [ConfirmedAuthSessionController::class, 'destroy'])
           ->middleware(['auth', 'auth.confirmed', 'crsf']);

    $router->get('confirm/magic-link', [ConfirmedAuthSessionController::class, 'store'])
           ->middleware(['auth', 'auth.unconfirmed'])
           ->name('confirm.magic-link');

    // 2FA
    if ($config->get('auth.features.two-factor-authentication')) {

        $router->post('two-factor/preferences', [TwoFactorAuthPreferenceController::class, 'store'])
               ->middleware(['auth', 'auth.confirmed'])
               ->name('two-factor.preferences');

        $router->delete('two-factor/preferences', [
            TwoFactorAuthPreferenceController::class, 'destroy',
        ])
               ->middleware(['auth', 'auth.confirmed']);

        $router->get('two-factor/challenge', [TwoFactorAuthSessionController::class, 'create'])
               ->name('2fa.challenge');

        $router->get('two-factor/recovery-codes', [RecoveryCodeController::class, 'index'])
               ->middleware(['auth', 'auth.confirmed', 'signed'])
               ->name('2fa.recovery-codes');

        $router->post('two-factor/recovery-codes', [RecoveryCodeController::class, 'update'])
               ->middleware(['auth', 'auth.confirmed', 'csrf:persist']);

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

        $router->get('register', [RegistrationLinkController::class, 'create'])->middleware('guest')
               ->name('register');

        $router->post('register', [RegistrationLinkController::class, 'store'])
               ->middleware('guest');

    }














