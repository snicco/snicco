<?php


    declare(strict_types = 1);

    use WPEmerge\Facade\WP;

?>

<h3>Hi <?= $user->first_name ?? '' ?>,</h3>

<p>
    Someone requested a confirmation link to access the secure area of: <?= WP::homeUrl() ?>
</p>
<p> Click <a href="<?= esc_url($magic_link) ?>"> here </a> to access the secure area.</p>
<p> This link will expire in 5 min.</p>
