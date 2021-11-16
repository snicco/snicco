<?php

declare(strict_types=1);

use Snicco\Support\WP;

?>

<h3>Hi <?= $user->first_name ?? '' ?>,</h3>

<p>
	Someone requested a login link to for your account at: <?= WP::siteName() ?>
</p>
<p> Click
	<a href="<?= esc_url($magic_link) ?>"> here</a>
    to log in to your account.
</p>
<p> This link will expire in <?= $expiration / 60 ?> minutes from now.</p>
