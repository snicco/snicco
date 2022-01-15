<?php

declare(strict_types=1);

?>
<h3>Hello <?= $user->first_name ?? '' ?>,</h3>

<p>
	Someone has requested a password reset for your account at: <?= esc_html($site_name) ?>
</p>
<p> If this was a mistake, ignore this email and nothing will happen. </p>

<p> To reset your password, click the link below and follow the instructions. The link will expire
    in <?= esc_html($expires / 60) ?> minutes. </p>

<a href="<?= esc_url($magic_link) ?>"> Reset my password.</a>
