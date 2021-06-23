<?php


    declare(strict_types = 1);

    use Illuminate\Support\ViewErrorBag;
    use WPEmerge\Session\Session;

    /** @var string $post_url */
    /** @var bool $invalid_email */
    /** @var ViewErrorBag $errors */
    /** @var string $csrf_field */
    /** @var string $last_recipient */

    /** @var Session $session */
    $lifetime = $session->get('auth.confirm.lifetime');
    $lifetime = $lifetime / 60;

?>

<div class="box">

    <?php if ($session->has('auth.confirm.success')) : ?>

        <div class="notification is-success is-light">
            <p class="is-size-5">Email sent successfully.</p>
        </div>

    <?php elseif ($invalid_email): ?>

        <div class="notification is-success is-light">

            <p class="is-size-5"><?= esc_html($errors->first('email')) ?></p>

        </div>

    <?php else  : ?>

        <div class="notification is-success is-light">
            <p class="is-size-5">We already sent you a confirmation email.</p>
        </div>

    <?php endif; ?>

    <p class="is-size-6">
            <span>Please check your email inbox at: <?= esc_html($last_recipient) ?>.</span>
            <br>
            <br>
            The confirmation link expires in <?= esc_html($lifetime) ?> minutes.
            <br>
            You can close this page now.
        </p>



    <form id="resend-email" action="<?= esc_attr($post_url) ?>" method="POST">

        <input
                type="email" name="email" id="email"
                class="<?= $invalid_email ? 'error' : '' ?>"
                value="<?= esc_attr($last_recipient) ?>"
                required
                hidden="hidden"
        >

        <?= $csrf_field ?>

        <button
                id="login_button"
                type="submit"
                class="button mt-4"
        >
            Resend Email

        </button>

    </form>


