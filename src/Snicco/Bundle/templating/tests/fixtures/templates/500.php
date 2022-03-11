<?php

declare(strict_types=1);

/**
 * @var string $safe_title
 * @var string $safe_details
 * @var int    $status_code
 * @var string $identifier
 */
echo sprintf('Title: %s', $safe_title);
echo sprintf('Details: %s', $safe_details);
echo sprintf('Status: %d', $status_code);
echo sprintf('Identifier: %s', $identifier);
