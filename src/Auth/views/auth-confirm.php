<?php


    declare(strict_types = 1);

    use Illuminate\Support\ViewErrorBag;
    use WPEmerge\Session\Session;
    use WPEmerge\View\ViewFactory;

    /** @var ViewFactory $view_factory */
    /** @var int $jail */

    /** @var Session $session */
    $old_email = $session->getOldInput('email', '');

    /** @var  ViewErrorBag $errors */
    $invalid_email = $errors->has('email');

?>


<?php if ($jail):

    echo $view_factory->render('auth-confirm-jail');

    ?>

<?php elseif ($session->has('auth.confirm.email.count')):

    echo $view_factory->render('auth-confirm-send',
        [
            'invalid_email' => $invalid_email,
            'old_email' => $old_email,
        ]
    );

    ?>

<?php else :

    echo $view_factory->render('auth-confirm-request-email',
        [
            'invalid_email' => $invalid_email,
            'old_email' => $old_email,
        ]
    );

    ?>

<?php endif; ?>





