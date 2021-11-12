<?php

declare(strict_types=1);

use Snicco\Routing\Router;
use Snicco\Application\Config;
use Snicco\Auth\Controllers\AccountController;
use Snicco\Auth\Controllers\AuthSessionController;
use Snicco\Auth\Controllers\RecoveryCodeController;
use Snicco\Auth\Controllers\ResetPasswordController;
use Snicco\Auth\Controllers\ForgotPasswordController;
use Snicco\Auth\Controllers\LoginMagicLinkController;
use Snicco\Auth\Controllers\RegistrationLinkController;
use Snicco\Auth\Controllers\TwoFactorAuthSetupController;
use Snicco\Auth\Controllers\ConfirmedAuthSessionController;
use Snicco\Auth\Controllers\TwoFactorAuthSessionController;
use Snicco\Auth\Controllers\AuthConfirmationEmailController;
use Snicco\Auth\Controllers\TwoFactorAuthPreferenceController;

/** @var Router $router */
/** @var Config $config */

// Dynamic endpoints which the developer can configure in the config to his likings.
$login = $config->get('auth.endpoints.login');
$magic_link = $config->get('auth.endpoints.magic-link');
$confirm = $config->get('auth.endpoints.confirm');

// Login
$router->middleware('guest')->group(function (Router $router) use ($config, $login, $magic_link) {
    $router->get("/$login", [AuthSessionController::class, 'create'])
           ->name('login');
    
    $router->post("/$login", [AuthSessionController::class, 'store'])
           ->middleware(['csrf', 'json']);
    
    // Magic-link
    if ($config->get('auth.authenticator') === 'email') {
        $router->post("$login/$magic_link", [LoginMagicLinkController::class, 'store'])
               ->middleware(['csrf', 'json'])
               ->name('login.create-magic-link');
        
        $router->get("$login/$magic_link", [AuthSessionController::class, 'store'])
               ->name('login.magic-link');
    }
});

// Logout
$router->get('/logout/{user_id}', [AuthSessionController::class, 'destroy'])
       ->middleware('signed:absolute')
       ->name('logout')
       ->andNumber('user_id');

// Auth Confirmation
$router->middleware(['auth', 'auth.unconfirmed'])->group(
    function (Router $router) use ($magic_link, $confirm) {
        $router->get("$confirm", [ConfirmedAuthSessionController::class, 'create'])->name(
            'confirm'
        );
        
        $router->post("$confirm", [ConfirmedAuthSessionController::class, 'store'])
               ->middleware('csrf');
        
        $router->get("$confirm/$magic_link", [ConfirmedAuthSessionController::class, 'store'])
               ->name('confirm.magic-link');
        
        $router->post("$confirm/$magic_link", [AuthConfirmationEmailController::class, 'store'])
               ->middleware('csrf')
               ->name('confirm.email');
    }
);

// 2FA
if ($config->get('auth.features.2fa')) {
    $two_factor = $config->get('auth.endpoints.2fa');
    $challenge = $config->get('auth.endpoints.challenge');
    
    $router->post("$two_factor/setup", [TwoFactorAuthSetupController::class, 'store'])
           ->middleware(['auth', 'auth.confirmed', 'json', 'csrf', '2fa.disabled'])
           ->name('2fa.setup.store');
    
    $router->post("$two_factor/preferences", [TwoFactorAuthPreferenceController::class, 'store'])
           ->middleware(['auth', 'auth.confirmed', 'json', 'csrf', '2fa.disabled'])
           ->name('2fa.preferences.store');
    
    $router->delete("$two_factor/preferences", [
        TwoFactorAuthPreferenceController::class,
        'destroy',
    ])
           ->middleware(['auth', 'auth.confirmed', 'json', 'csrf', '2fa.enabled'])->name(
            '2fa.preferences.destroy'
        );
    
    $router->get("$two_factor/$challenge", [TwoFactorAuthSessionController::class, 'create'])
           ->name('2fa.challenge');
    
    // recovery codes.
    $router->name('2fa.recovery-codes')->middleware(['auth', 'auth.confirmed', '2fa.enabled'])
           ->group(function (Router $router) {
               $router->get('two-factor/recovery-codes', [
                   RecoveryCodeController::class,
                   'index',
               ]);
        
               $router->put('two-factor/recovery-codes', [
                   RecoveryCodeController::class,
                   'update',
               ])->middleware(['json', 'csrf']);
           });
}

// password resets
if ($config->get('auth.features.password-resets')) {
    $forgot = $config->get('auth.endpoints.forgot-password');
    $reset = $config->get('auth.endpoints.reset-password');
    
    // forgot-password
    $router->get("/$forgot", [ForgotPasswordController::class, 'create'])
           ->middleware('guest')
           ->name('forgot.password');
    
    $router->post("/$forgot", [ForgotPasswordController::class, 'store'])
           ->middleware(['csrf', 'guest']);
    
    // reset-password
    $router->get("/$reset", [ResetPasswordController::class, 'create'])
           ->middleware('signed:absolute')
           ->name('reset.password');
    
    $router->put("/$reset", [ResetPasswordController::class, 'update'])
           ->middleware(['csrf', 'signed:absolute']);
}

// registration
if ($config->get('auth.features.registration')) {
    $register = $config->get('auth.endpoints.register');
    
    $router->middleware('guest')->group(function ($router) use ($register) {
        $router->get("/$register", [RegistrationLinkController::class, 'create'])
               ->name('register');
        
        $router->post("/$register", [RegistrationLinkController::class, 'store'])->middleware(
            'csrf'
        );
    });
}

// accounts
$router->name('accounts')->group(function (Router $router) use ($config) {
    $accounts = $config->get('auth.endpoints.accounts');
    $create = $config->get('auth.endpoints.accounts_create');
    
    $router->get("/$accounts/$create", [AccountController::class, 'create'])
           ->middleware(['guest', 'signed:absolute'])
           ->name('create');
    
    $router->post("/$accounts", [AccountController::class, 'store'])
           ->middleware(['guest', 'csrf', 'signed'])
           ->name('store');
    
    $router->delete("/$accounts/{user_id}", [AccountController::class, 'destroy'])
           ->middleware(['auth', 'csrf'])
           ->name('delete')
           ->andNumber('user_id');
});












