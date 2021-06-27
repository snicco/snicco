<?php


    declare(strict_types = 1);

?>

<div class="box">

    <?php if (! $errors->has('message')) : ?>

        <div class="notification is-info is-light">
            <p class="is-size-5">
                This page is part of the secure area of the application!
            </p>
            <p class="is-size-5 mt-2 mb-2">
                You need to confirm your access before you can proceed.
            </p>
        </div>

    <?php else : ?>
        <div class="notification is-danger is-light mt-2">
            <p class="is-size-5">
                We could not confirm your authentication.
            </p>
            <p class="is-size-5 mt-2">
                Your link was either invalid or expired.
            </p>
        </div>

        <?php if ( $session->pull('auth.confirm.can_request_another_email', false )) : ?>

            <p> Please request a new link to confirm your authentication.</p>

            <button class="button submit" onClick="window.location.reload();">Send new confirmation email</button>

        <?php endif; ?>

    <?php endif; ?>

    <?php if ($session->pull('auth.confirm.email_sent')) : ?>

        <div class="notification is-success is-light">
            <p class="is-size-6">
                We have sent a confirmation link to the email address linked with this account. <br> <br> By clicking the confirmation link you can continue where you left of.
            </p>
        </div>

    <?php endif; ?>

    <?php if ($period = $session->pull('auth.confirm.cool_off_period')) : ?>

        <p class="is-size-5">
            You can request a new confirmation email in <?= $period ?> seconds by refreshing this page.
        </p>


    <?php endif; ?>

</div>
