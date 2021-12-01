<?php

declare(strict_types=1);

$site_name = esc_html(\Snicco\Support\WP::siteName());

?>

<h3>Hi,</h3>

<p>
	Someone tried to registered an account at <?= $site_name ?> with your email address.
</p>
<p>
	If this was a mistake, you can ignore this email and nothing will happen.
</p>
<p>
	To confirm your account click the following link:
</p>
<p>
	<a href="<?= esc_url($magic_link) ?>"> Confirm your account</a>
</p>
Sincerely,
<br>
<?= esc_html($site_name) ?>

