<?php

declare(strict_types=1);

use Snicco\HttpRouting\Routing\Router;
use Snicco\Auth\Controllers\AccountAbstractController;
use Snicco\Component\Core\Configuration\WritableConfig;
use Snicco\Auth\Controllers\AuthSessionAbstractController;
use Snicco\Auth\Controllers\RecoveryCodeAbstractController;
use Snicco\Auth\Controllers\ResetPasswordAbstractController;
use Snicco\Auth\Controllers\ForgotPasswordAbstractController;
use Snicco\Auth\Controllers\LoginMagicLinkAbstractController;
use Snicco\Auth\Controllers\RegistrationLinkAbstractController;
use Snicco\Auth\Controllers\TwoFactorAuthSetupAbstractController;
use Snicco\Auth\Controllers\ConfirmedAuthSessionAbstractController;
use Snicco\Auth\Controllers\TwoFactorAuthSessionAbstractController;
use Snicco\Auth\Controllers\AuthConfirmationEmailAbstractController;
use Snicco\Auth\Controllers\TwoFactorAuthPreferenceAbstractController;

/** @var Router $router */
/** @var WritableConfig $config */

// Dynamic endpoints which the developer can configure in the config to his likings.
$login = $config->get('auth.endpoints.login');
$magic_link = $config->get('auth.endpoints.magic-link');
$confirm = $config->get('auth.endpoints.confirm');

// Login
$router->middleware('guest')->createInGroup(
    function (Router $router) use ($config, $login, $magic_link) {
        $router->get("/$login", [AuthSessionAbstractController::class, 'create'])
               ->name('login');
        
        $router->post("/$login", [AuthSessionAbstractController::class, 'store'])
               ->middleware(['csrf', 'json']);
        
        // Magic-link
        if ($config->get('auth.authenticator') === 'email') {
            $router->post("$login/$magic_link", [LoginMagicLinkAbstractController::class, 'store'])
                   ->middleware(['csrf', 'json'])
                   ->name('login.create-magic-link');
            
            $router->get("$login/$magic_link", [AuthSessionAbstractController::class, 'store'])
                   ->name('login.magic-link');
        }
    }
);

// Logout @todo user id param is not needed here.
$router->get('/logout/{user_id}', [AuthSessionAbstractController::class, 'destroy'])
       ->middleware('signed:absolute')
       ->name('logout')
       ->andNumber('user_id');

// Auth Confirmation
$router->middleware(['auth', 'auth.unconfirmed'])->createInGroup(
    function (Router $router) use ($magic_link, $confirm) {
        $router->get("$confirm", [ConfirmedAuthSessionAbstractController::class, 'create'])->name(
            'confirm'
        );
        
        $router->post("$confirm", [ConfirmedAuthSessionAbstractController::class, 'store'])
               ->middleware('csrf');
        
        $router->get(
            "$confirm/$magic_link",
            [ConfirmedAuthSessionAbstractController::class, 'store']
        )
               ->name('confirm.magic-link');
        
        $router->post(
            "$confirm/$magic_link",
            [AuthConfirmationEmailAbstractController::class, 'store']
        )
               ->middleware('csrf')
               ->name('confirm.email');
    }
);

// 2FA
if ($config->get('auth.features.2fa')) {
    $two_factor = $config->get('auth.endpoints.2fa');
    $challenge = $config->get('auth.endpoints.challenge');
    
    $router->post("$two_factor/setup", [TwoFactorAuthSetupAbstractController::class, 'store'])
           ->middleware(['auth', 'auth.confirmed', 'json', 'csrf', '2fa.disabled'])
           ->name('2fa.setup.store');
    
    $router->post(
        "$two_factor/preferences",
        [TwoFactorAuthPreferenceAbstractController::class, 'store']
    )
           ->middleware(['auth', 'auth.confirmed', 'json', 'csrf', '2fa.disabled'])
           ->name('2fa.preferences.store');
    
    $router->delete("$two_factor/preferences", [
        TwoFactorAuthPreferenceAbstractController::class,
        'destroy',
    ])
           ->middleware(['auth', 'auth.confirmed', 'json', 'csrf', '2fa.enabled'])->name(
            '2fa.preferences.destroy'
        );
    
    $router->get("$two_factor/$challenge", [TwoFactorAuthSessionAbstractController::class, 'create']
    )
           ->name('2fa.challenge');
    
    // recovery codes.
    $router->name('2fa.recovery-codes')->middleware(['auth', 'auth.confirmed', '2fa.enabled'])
           ->createInGroup(function (Router $router) {
               $router->get('two-factor/recovery-codes', [
                   RecoveryCodeAbstractController::class,
                   'index',
               ]);
        
               $router->put('two-factor/recovery-codes', [
                   RecoveryCodeAbstractController::class,
                   'update',
               ])->middleware(['json', 'csrf']);
           });
}

// password resets
if ($config->get('auth.features.password-resets')) {
    $forgot = $config->get('auth.endpoints.forgot-password');
    $reset = $config->get('auth.endpoints.reset-password');
    
    // forgot-password
    $router->get("/$forgot", [ForgotPasswordAbstractController::class, 'create'])
           ->middleware('guest')
           ->name('forgot.password');
    
    $router->post("/$forgot", [ForgotPasswordAbstractController::class, 'store'])
           ->middleware(['csrf', 'guest']);
    
    // reset-password
    $router->get("/$reset", [ResetPasswordAbstractController::class, 'create'])
           ->middleware('signed:absolute')
           ->name('reset.password');
    
    $router->put("/$reset", [ResetPasswordAbstractController::class, 'update'])
           ->middleware(['csrf', 'signed:absolute']);
}

// registration
if ($config->get('auth.features.registration')) {
    $register = $config->get('auth.endpoints.register');
    
    $router->middleware('guest')->createInGroup(function ($router) use ($register) {
        $router->get("/$register", [RegistrationLinkAbstractController::class, 'create'])
               ->name('register');
        
        $router->post("/$register", [RegistrationLinkAbstractController::class, 'store'])
               ->middleware(
                   'csrf'
               );
    });
}

// accounts
$router->name('accounts')->createInGroup(function (Router $router) use ($config) {
    $accounts = $config->get('auth.endpoints.accounts');
    $create = $config->get('auth.endpoints.accounts_create');
    
    $router->get("/$accounts/$create", [AccountAbstractController::class, 'create'])
           ->middleware(['guest', 'signed:absolute'])
           ->name('create');
    
    $router->post("/$accounts", [AccountAbstractController::class, 'store'])
           ->middleware(['guest', 'csrf', 'signed'])
           ->name('store');
    
    $router->delete("/$accounts/{user_id}", [AccountAbstractController::class, 'destroy'])
           ->middleware(['auth', 'csrf'])
           ->name('delete')
           ->andNumber('user_id');
});












