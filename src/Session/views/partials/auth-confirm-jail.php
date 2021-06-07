<?php


    declare(strict_types = 1);

    use Carbon\Carbon;

    /** @var int $jail */

?>
<p id="form-heading">
    This page is part of the secure area of the application!
</p>
<hr>
<p class="error message">
    You have requested to many emails. You can request a new confirmation
    link
    in:
    <?= Carbon::now()->diffInMinutes(Carbon::createFromTimestamp($jail)) ?>
    minute/s.
</p>

<p> Any previously sent confirmation email can still be used.</p>
