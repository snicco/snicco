<?php

    declare(strict_types = 1);

    use Illuminate\Support\ViewErrorBag;
    use WPEmerge\Session\SessionStore;

    /** @var string $post_url */
    /** @var bool $invalid_email */
    /** @var ViewErrorBag $errors */
    /** @var string $csrf_field */
    /** @var string $last_recipient */

    /** @var SessionStore $session */
    $lifetime = $session->get('auth.confirm.lifetime');
    $lifetime = $lifetime / 60;


?>

<?php if ($session->has('auth.confirm.success')) : ?>
    <p class="success message">
        Email sent successfully.
    </p>
<?php else  : ?>
    <p id="form-heading">
        This page is part of the secure area of the application!
    </p>
    <p class="notice message">
        We already sent you a confirmation email.
    </p>
<?php endif; ?>


<p class="text-large">
    <span>Please check your email inbox at: <?= $last_recipient ?>.</span>
    <br>
    <br>
    The confirmation link expires in <?= $lifetime ?> minutes.
    <br>
    You can close this page now.
</p>

<form id="resend-email" class="form" action="<?= esc_attr($post_url) ?>" method="POST">

    <?php if ($invalid_email) : ?>

        <p class='error message'> <?= $errors->first('email') ?> </p>

    <?php endif; ?>

    <div class="form-group">
        <input type="email" name="email" id="email"
               class="<?= $invalid_email ? 'error' : '' ?>"
               value="<?= esc_attr($last_recipient) ?>" required
               hidden="hidden">
    </div>
    <?= $csrf_field ?>
    <button class="submit" type="submit">Resend Email</button>

</form>
