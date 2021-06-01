<?php


    declare(strict_types = 1);

    use Carbon\Carbon;

    $ds = DIRECTORY_SEPARATOR;

    /** @var  \Illuminate\Support\ViewErrorBag $errors */
    $error = $errors->has('email');

    /** @var \WPEmerge\Session\SessionStore $session */
    $old_email = $session->getOldInput('email', '');

    $lifetime = $session->get('auth.confirm.lifetime', 300);

    $lifetime = $lifetime/60;

?>

<html <?php language_attributes() ?>>
<head>

    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title>Authentication Required</title>

</head>

<body>

<style>

    .main {
        display: flex;
        flex-direction: row;
        min-height: 80vh;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }

    body {
        background: #f0f0f1;
        min-width: 0;
        color: #3c434a;
        font-size: 16px;
        line-height: 1.4;
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

    #form {
        margin-top: 30px;
    }

    .message {
        color: #fff;
        padding: 10px 5px;
    }

    .message.error {

        border: #ce4646 solid 2px;
        background: rgb(193, 71, 71);

    }

    .message.success {

        border: rgb(85 171 69) solid 2px;
        background: rgb(85 171 69);
        padding: 10px;
        font-size: 18px;

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

            if ($session->get('auth.confirm.success', false)) {


                ?>

                <p id="form-heading" class="success message">
                    Email sent successfully.
                </p>
                <p class="text-large">
                    <span>Please check your email inbox at: <?= $old_email ?>.</span>
                    <br>
                    <br>
                        The confirmation link expires in <?= $lifetime ?> minutes from now.
                    <br>
                    You can close this page now.
                </p>

                <?php

                }
                else {

                ?>

                <p id="form-heading">
                    This is the secure part of the application!
                </p>

                <hr>

                <p>Enter your email to receive a confirmation
                    email and click the link to confirm access this page.</p>

                <form id="form" action="<?= esc_attr($post_url) ?>" method="POST">

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



