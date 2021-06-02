<?php


    declare(strict_types = 1);

    use Carbon\Carbon;
    use Illuminate\Support\ViewErrorBag;
    use WPEmerge\Session\SessionStore;

    $ds = DIRECTORY_SEPARATOR;

    /** @var  ViewErrorBag $errors */
    $error = $errors->has('email');

    /** @var SessionStore $session */
    $old_email = $session->getOldInput('email', '');

    $lifetime = $session->get('auth.confirm.lifetime');

    $lifetime = $lifetime / 60;

?>

<html <?php language_attributes() ?>>
<head>

    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title>Authentication Required</title>

</head>

<body>

<style>

    body {
        background: #f0f0f1;
        min-width: 0;
        color: #3c434a;
        font-size: 16px;
        line-height: 1.4;
    }

    .main {
        display: flex;
        flex-direction: row;
        min-height: 80vh;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }

    #login-wrapper {
        width: 360px;
        margin: auto;
        color: #3c434a;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        line-height: 1.4;
        padding: 26px 24px 34px;
        font-weight: 400;
        overflow: hidden;
        background: #fff;
        border: 1px solid #c3c4c7;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
    }

    .form-group {

        display: flex;
        flex-direction: column;

    }

    label {

        line-height: 1.5;
        display: inline-block;
        margin-bottom: 6px;

    }

    input {

        box-sizing: border-box;
        font-family: inherit;
        font-weight: inherit;
        box-shadow: 0 0 0 transparent;
        border-radius: 4px;
        border: 1px solid #8c8f94;
        color: #2c3338;
        font-size: 24px;
        line-height: 1.33333333;
        width: 100%;
        padding: .1875rem .3125rem;
        margin: 0 6px 16px 0;
        min-height: 40px;
        max-height: none;
        background: #fff;

    }

    button {

        display: inline-block;
        font-size: 16px;
        margin: 0;
        width: 100%;
        cursor: pointer;
        border-width: 1px;
        border-style: solid;
        -webkit-appearance: none;
        border-radius: 3px;
        white-space: nowrap;
        box-sizing: border-box;
        background: #2271b1;
        border-color: #2271b1;
        color: #fff;
        text-decoration: none;
        text-shadow: none;
        float: right;
        vertical-align: baseline;
        min-height: 32px;
        line-height: 2.30769231;
        padding: 0 12px;

    }

    #form-heading {
        font-size: 18px;
        font-weight: 500;
    }

    .form {
        margin-top: 30px;
    }

    .message {

        padding: 10px 10px;
        border-radius: 1px;
        border-left: solid 3px;
        box-shadow: 0px 2px 10px 0px #47525d21;
        font-size: 16px;

    }

    .message.notice {

        border-color: #0a5b88;

    }

    .message.error {

        border-color: #ce4646;

    }

    .message.success {

        border-color: rgb(85 171 69);

    }

    .text-large {
        font-size: 16px;
    }

    span.text-large {
        font-size: 18px;
    }

</style>

<section class="main">


    <div id="login-wrapper">

        <?php

            if ($session->get('auth.confirm.success', false) || $jail !== false || $session->has('auth.confirm.email.count')) {


                if ($jail === false) {


                    ?>
                    <?php if ($session->has('auth.confirm.success')) : ?>
                        <p class="success message">
                            Email sent successfully.
                        </p>
                    <?php else  : ?>
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

                    <form id="resend-email" class="form" action="<?= esc_attr($post_url) ?>"
                          method="POST">

                        <?php

                            if ($error) {

                                echo "<p class='error message'> {$errors->first('email')} </p>";

                            }

                        ?>
                        <div class="form-group">
                            <input type="email" name="email" id="email"
                                   class="<?= $error ? 'error' : '' ?>"
                                   value="<?= esc_attr($last_recipient) ?>" required
                                   hidden="hidden">
                        </div>
                        <?= $csrf_field ?>
                        <button class="submit" type="submit">Resend Email</button>

                    </form>

                    <?php
                }

                else {

                    ?>

                    <p id="form-heading">
                        This page is part of the secure area of the application!
                    </p>
                    <hr>
                    <p class="error message">
                        You have requested to many emails. You can request a new confirmation link
                        in:
                        <?= Carbon::now()->diffInMinutes(Carbon::createFromTimestamp($jail)) ?>
                        minute/s.
                    </p>

                    <p> Any previously sent confirmation email can still be used.</p>


                    <?php

                }

                ?>

                <?php


            }

            else {

                ?>

                <p id="form-heading">
                    This page is part of the secure area of the application!
                </p>
                <p> You need to confirm your access before you can proceed.</p>

                <hr>

                <p>Enter your email to receive a confirmation
                    email and click the link to confirm access this page.</p>

                <form id="send" class="form" action="<?= esc_attr($post_url) ?>" method="POST">

                    <?php

                        if ($error) {

                            echo "<p class='error message'> {$errors->first('email')} </p>";

                        }

                    ?>
                    <div class="form-group">
                        <input type="email" name="email" id="email"
                               class="<?= $error ? 'error' : '' ?>"
                               value="<?= esc_attr($old_email) ?>" required>
                    </div>
                    <?= $csrf_field ?>
                    <button class="submit" type="submit">Send Confirmation Email</button>

                </form>

                <?php
            }

        ?>


    </div>


</section>

</body>



