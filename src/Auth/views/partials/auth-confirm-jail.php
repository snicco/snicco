<?php


    declare(strict_types = 1);

    use Carbon\Carbon;

    /** @var int $jail */

?>

<div class="box">

    <div class="notification is-danger is-light">

        <p class="is-size-5">
            You have requested to many emails.
        </p>

    </div>

    <p class="mt-4">
        You have requested to many emails. You can request a new confirmation link in:
        <span class="has-background-info-light p-1"> <?= esc_html(Carbon::now()->diffInMinutes(Carbon::createFromTimestamp($jail))) ?>
        minute/s.
        </span>
    </p>

    <p class="mt-2">
        Any previously sent confirmation email can still be used.
    </p>

</div>
