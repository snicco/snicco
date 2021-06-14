<?php


    declare(strict_types = 1);

    use WPEmerge\EnhancedAuth\Controllers\LoginController;
    use WPEmerge\Routing\Router;

    /** @var Router $router */

    $router->get('/login', function (\WPEmerge\Http\Psr7\Request $request) {


        ob_start();

        ?>

        <?php if ($request->has('native')) : ?>
            <form action="/wp-login.php" method="POST">
                <div style="display: block">
                    <label for="user_login"><?php _e('Username or Email Address'); ?>
                        <input type="text" name="log" id="user_login" class="input" value=""
                               size="20"
                               autocapitalize="off"/>
                    </label>

                    <label for="user_pass"><?php _e('Password'); ?>
                        <input type="password" name="pwd" id="user_pass"
                               class="input password-input" value=""
                               size="20"/>
                    </label>

                    <button type="submit">Log in native</button>
                </div>
            </form>
        <?php else : ?>

            <form action="<?= wp_login_url() ?>" method="POST">
                <div style="display: block">
                    <label for="user_login"><?php _e('Username or Email Address'); ?>
                        <input type="text" name="log" id="user_login" class="input" value="<?= $request->session()->getOldInput('username', '') ?>"
                               size="20"
                               autocapitalize="off"/>
                    </label>

                    <label for="user_pass"><?php _e('Password'); ?>
                        <input type="password" name="pwd" id="user_pass"
                               class="input password-input" value=""
                               size="20"/>
                    </label>
                    <button type="submit">Log in</button>
                    <?php
                        $errors = $request->session()->errors();
                        if ($errors->has('message')) : ?>

                        <h3> <?= $errors->first('message') ?></h3>

                    <?php endif; ?>
                </div>
            </form>

        <?php endif; ?>

        <?php

        return ob_get_clean();


    })
           ->middleware(['secure', 'guest'])
           ->name('login');


    $router->post('/login', LoginController::class)
           ->middleware(['secure', 'guest'])
           ->name('login');



