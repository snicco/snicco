<?php


    declare(strict_types = 1);

?>

<div class="box">
<?php if ($session->has('_password_reset.success_message')) : ?>
<div class="notification is-success is-light">
    <?= $session->get('_password_reset.success_message') ?>
</div>
<?php endif; ?>
    <a href="<?= wp_login_url() ?>" class="is-link"> Proceed to login</a>
</div>