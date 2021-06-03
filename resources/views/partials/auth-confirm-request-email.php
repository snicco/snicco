<?php


    declare(strict_types = 1);

    use Illuminate\Support\ViewErrorBag;

    /** @var string $post_url */
    /** @var ViewErrorBag $errors */
    /** @var bool $invalid_email */
    /** @var string $csrf_field */

    /** @var string $old_email */


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

    <div class="form-group">
        <input type="email" name="email" id="email"
               class="<?= $invalid_email ? 'error' : '' ?>"
               value="<?= esc_attr($old_email) ?>" required>
    </div>
    <?= $csrf_field ?>
    <button class="submit" type="submit">Send Confirmation Email</button>

</form>