<?php

declare(strict_types=1);

/**
 * @var string  $mail_content
 * @var ?string $extra
 */
echo '<h1>' . $mail_content . '</h1>';

if (isset($extra)) {
    echo "\n" . $extra;
}
