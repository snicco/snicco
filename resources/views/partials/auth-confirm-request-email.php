<?php


    declare(strict_types = 1);

    use Illuminate\Support\ViewErrorBag;
    use WPEmerge\Session\SessionStore;

    /** @var string $post_url */
    /** @var ViewErrorBag $errors */
    /** @var bool $invalid_email */
    /** @var string $csrf_field */
    /** @var string $old_email */
    /** @var SessionStore $session */

    $email_failed = $session->has('auth.confirm.email_sending_failed');

?>

<p id="form-heading">
    This page is part of the secure area of the application!
</p>
<p> You need to confirm your access before you can proceed.</p>

<hr>

<p>Enter your email to receive a confirmation
    email and click the link to confirm access this page.</p>

<form id="send" class="form" action="<?= esc_attr($post_url) ?>" method="POST">


    <?php if ($invalid_email) : ?>

        <p class='error message'> <?= $errors->first('email') ?> </p>

    <?php endif; ?>

    <?php if ($email_failed) : ?>

        <p class='error message'> Error: The email could not be sent. Please try again. </p>

    <?php endif; ?>

    <div class="form-group">
        <input type="email" name="email" id="email"
               class="<?= $invalid_email ? 'error' : '' ?>"
               value="<?= esc_attr($old_email) ?>" required>
    </div>
    <?= $csrf_field ?>
    <button type="submit" class="submit"> <?= $email_failed ? 'Try again' : 'Send Confirmation Email'; ?></button>

</form>