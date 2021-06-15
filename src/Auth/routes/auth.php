<?php


    declare(strict_types = 1);

    use WPEmerge\Auth\Controllers\AuthController;
    use WPEmerge\Auth\Controllers\ConfirmAuthController;
    use WPEmerge\Auth\Controllers\ForgotPasswordController;
    use WPEmerge\Auth\Controllers\ResetPasswordController;
    use WPEmerge\Routing\Router;
    use WPEmerge\Auth\Controllers\ConfirmAuthMagicLinkController;
    use WPEmerge\Auth\Controllers\LogoutController;

    /** @var Router $router */

    $router->get('/login', [AuthController::class, 'create'])
           ->middleware(['secure', 'guest:/wp-admin/'])
           ->name('login');

    $router->post('/login', AuthController::class)
           ->middleware(['secure', 'csrf', 'guest:/wp-admin/'])
           ->name('login');

    $router->get('forgot-password', [ForgotPasswordController::class, 'create'])
           ->middleware(['secure', 'guest:/wp-admin/'])
           ->name('forgot.password.show');

    $router->post('forgot-password', [ForgotPasswordController::class, 'store'])
           ->middleware(['secure', 'csrf', 'guest:/wp-admin/'])
           ->name('forgot.password.create');

    $router->get('/password-reset', [ResetPasswordController::class, 'create'])
           ->middleware(['secure', 'signed'])
           ->name('reset.password.create')
           ->andNumber('user_id');

     $router->post('/password-reset', [ResetPasswordController::class, 'update'])
           ->middleware(['secure', 'csrf'])
           ->name('reset.password.update');

     $router->get('/password-reset/success', [ResetPasswordController::class, 'show'])
           ->middleware(['secure'])
           ->name('reset.password.show');


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

    $router->get('/auth/logout/{user_id}', LogoutController::class)
           ->middleware('signed')
           ->name('auth.logout')
           ->andAlphaNumerical('user_id');



