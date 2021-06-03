<?php


    declare(strict_types = 1);

    use Illuminate\Support\ViewErrorBag;
    use WPEmerge\Session\Session;
    use WPEmerge\View\ViewFactory;

    /** @var ViewFactory $view */
    /** @var int $jail */

    /** @var Session $session */
    $old_email = $session->getOldInput('email', '');

    /** @var  ViewErrorBag $errors */
    $invalid_email = $errors->has('email');

?>

<html <?php language_attributes() ?> >
<head>

    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title>Authentication Required</title>

</head>

<body>

<style>

    #logo {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        text-align: center;
        transition-property: border, background, color;
        transition-duration: .05s;
        transition-timing-function: ease-in-out;
        background-image: none, url(/wp-admin/images/wordpress-logo.svg);
        background-size: 84px;
        background-position: center top;
        background-repeat: no-repeat;
        color: #3c434a;
        height: 84px;
        font-size: 20px;
        font-weight: 400;
        line-height: 1.3;
        margin: 0 auto 25px;
        padding: 0;
        text-decoration: none;
        width: 84px;
        text-indent: -9999px;
        outline: 0;
        overflow: hidden;
        display: block;
    }

    body {
        background: #f0f0f1;
        min-width: 0;
        color: #3c434a;
        font-size: 16px;
        line-height: 1.4;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }

    .main {
        display: flex;
        flex-direction: row;
        min-height: 80vh;
    }

    #login-wrapper {
        margin: auto;
        width: 380px;
    }

    #login-box {
        color: #3c434a;
        line-height: 1.4;
        padding: 26px 24px 34px;
        font-weight: 400;
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
        box-shadow: 0 0 0 transparent;
        border-radius: 4px;
        border: 1px solid #8c8f94;
        color: #2c3338;
        font-size: 18px;
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
        padding: 15px 0px;
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

        <a href="https://wordpress.org/" id="logo"></a>

        <div id="login-box">


            <?php if ($jail):

                echo $view->render('auth-confirm-jail');

                ?>

            <?php elseif ($session->has('auth.confirm.email.count')):

                echo $view->render('auth-confirm-send',
                    [
                        'invalid_email' => $invalid_email,
                        'old_email' => $old_email,
                    ]
                );

                ?>

            <?php else :

                echo $view->render('auth-confirm-request-email',
                    [
                        'invalid_email' => $invalid_email,
                        'old_email' => $old_email,
                    ]
                );

                ?>

            <?php endif; ?>


        </div>

    </div>

</section>

</body>
</html>


