<?php

declare(strict_types=1);

use Snicco\Support\WP;

?>

<h3>Hi <?= $user->first_name ?? '' ?>,</h3>

<p>
	Someone requested a confirmation link to access the secure area of: <?= WP::siteName() ?>
</p>
<p> Click
	<a href="<?= esc_url($magic_link) ?>"> here</a>
    to access the secure area.
</p>
<p> This link will expire in <?= $lifetime / 60 ?> minutes from now.</p>
