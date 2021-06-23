<?php


    declare(strict_types = 1);

    use Illuminate\Support\ViewErrorBag;
    use WPEmerge\Session\Session;

    /** @var ViewErrorBag $errors */

    /** @var Session $session */

?>




<?php if ($is_interim_login === true) : ?>

    <div class="notification is-info is-light">
        Your session has expired. Please log back in to continue.
    </div>

<?php endif; ?>

<form method="POST" action="<?= esc_attr($post_url) ?>" class="box">

    <?php if ($errors->has('message')) : ?>

        <div class="notification is-danger is-light">
            <?= $errors->first('message') ?>
        </div>

    <?php endif; ?>

    <!--Interim login-->
    <?php if ($is_interim_login) : ?>
        <input type="hidden" name="is_interim_login" value="1">
    <?php endif; ?>

    <!--CSRF field-->
    <?= $csrf->asHtml() ?>

    <!--Redirect to-->
<!--    <input type="hidden" name="redirect_to"-->
<!--           value="--><?//= esc_attr($redirect_to) ?><!--">-->

    <!--Username-->
    <div class="field">
        <label for="" class="label">Username or email</label>

        <div class="control has-icons-left">

            <input name="log" type="text" placeholder="e.g. bobsmith@gmail.com"
                   value="<?= esc_attr($session->getOldInput('username', '')) ?>"
                   class="input <?= $errors->count() ? 'is-danger' : '' ?>" required
                   autocomplete="username">

            <span class="icon is-small is-left">
                                      <i class="fa fa-envelope"></i>
                                 </span>

        </div>
    </div>

    <!--Password-->
    <div class="field">
        <label for="" class="label">Password</label>
        <div class="control has-icons-left">
            <input name="pwd" type="password" placeholder="*******"
                   class="input <?= $errors->count() ? 'is-danger' : '' ?>" required
                   autocomplete="current-password">
            <span class="icon is-small is-left">
                  <i class="fa fa-lock"></i>
                </span>
        </div>
    </div>

    <!--Remember me-->
    <?php if ($allow_remember) : ?>
        <div class="field">
            <label for="" class="checkbox">
                <input name="remember_me"
                       type="checkbox" <?= $session->getOldInput('remember_me', 'off') === 'on' ? 'checked' : '' ?>>
                Remember me
            </label>
        </div>
    <?php endif; ?>

    <!--    Submit Button -->
    <div class="field">
        <button id="login_button" class="button">
            Login
        </button>
    </div>
    <?php if ($allow_password_reset) : ?>

        <a href="<?= esc_url($forgot_password_url) ?>" class="text-sm-left"> Forgot password?</a>

    <?php endif; ?>

</form>





