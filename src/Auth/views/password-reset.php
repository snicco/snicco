<?php


    declare(strict_types = 1);

    /** @var \Illuminate\Support\ViewErrorBag $errors */
    /** @var \WPEmerge\Session\Session $session */

?>

<?php if ($session->has('_password_reset.success_message')) : ?>

    <div class="box">
            <div class="notification is-success is-light">
                <?= $session->get('_password_reset.success_message') ?>
            </div>
        <a href="<?= wp_login_url() ?>" class="is-link"> Proceed to login</a>
    </div>
<?php else : ?>
    <form method="POST" action="<?= esc_attr($post_to) ?>" class="box">


        <?php if ($errors->count()): ?>

            <div class="notification is-danger is-light">

                <ul style="list-style: inherit">
                    <?php foreach ($errors->all() as $error): ?>

                        <li> <?= esc_html($error) ?></li>

                    <?php endforeach; ?>
                </ul>

            </div>

        <?php else : ?>

            <div class="notification is-info is-light">
                <p> Choose and confirm your new password. </p>
            </div>
        <?php endif; ?>


        <?= $csrf_field ?>
        <input name="id" type="text" hidden value="<?= esc_attr($user_id) ?>">
        <input name="signature" type="text" hidden value="<?= esc_attr($signature) ?>">

        <div class="field">
            <label for="" class="label">Password</label>
            <div class="control has-icons-left">

                <input

                        name="password" type="password"
                        placeholder="Enter your new password"
                        class="input <?= $errors->count() ? 'is-danger' : '' ?>"
                        value="<?= esc_attr($session->getOldInput('password', '')) ?>"
                        required
                        autocomplete="new-password"

                >

                <span class="icon is-small is-left">
                  <i class="fa fa-lock"></i>
             </span>

            </div>
        </div>
        <div class="field">
            <label for="" class="label">Confirmation</label>
            <div class="control has-icons-left">
                <input

                        name="password_confirmation"
                        type="password"
                        placeholder="Confirm your password"
                        class="input <?= $errors->count() ? 'is-danger' : '' ?>"
                        required
                        autocomplete="new-password"

                >
                <span class="icon is-small is-left">
                  <i class="fa fa-lock"></i>
            </span>
            </div>
        </div>
        <div class="field">
            <button id="login_button" class="button ">
                Update password
            </button>
        </div>

    </form>
<?php endif; ?>


