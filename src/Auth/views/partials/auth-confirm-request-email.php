<?php


    declare(strict_types = 1);

    use Illuminate\Support\ViewErrorBag;
    use WPEmerge\Session\Session;

    /** @var string $post_url */
    /** @var ViewErrorBag $errors */
    /** @var bool $invalid_email */
    /** @var string $csrf_field */
    /** @var string $old_email */
    /** @var Session $session */

    $email_failed = $session->has('auth.confirm.email_sending_failed');

?>


<div class="box">

    <?php if ($invalid_email) : ?>

        <div class="notification is-danger is-light">
            <p class="is-size-5">
                <?= $errors->first('email') ?>
            </p>
        </div>

    <?php elseif($email_failed) : ?>

        <div class="notification is-danger is-light">
            <p class="is-size-5">
                Error: The email could not be sent. Please try again.
            </p>
        </div>

    <?php else : ?>

        <div class="notification is-info is-light">
            <p class="is-size-5">
                This page is part of the secure area of the application!
            </p>
            <p class="is-size-5 mt-2">
                You need to confirm your access before you can proceed.
            </p>
        </div>

        <p class="is-size-6">
            Enter your email to receive a confirmation
            email and click the link to confirm access this page.
        </p>

    <?php endif; ?>

    <form id="send" class="mt-4" action="<?= esc_attr($post_url) ?>" method="POST">

        <div class="field">

            <label for="" class="label">Your Account Email</label>
            <div class="control has-icons-left is-size-6">
                <input
                        name="email"
                        type="text"
                        placeholder="e.g. bobsmith@gmail.com"
                        value="<?= esc_attr($old_email) ?>"
                        class="input is-normal <?= $errors->count() ? 'is-danger' : '' ?>"
                        required
                        size="20"
                >

                <span class="icon is-small is-left">
                     <i class="fa fa-envelope"> </i>
                </span>
            </div>
        </div>

        <?= $csrf_field ?>
        <button
                id="login_button"
                type="submit"
                class="button"
        >
            <?= $email_failed ? 'Try again' : 'Send Confirmation Email'; ?>

        </button>

    </form>
</div>


