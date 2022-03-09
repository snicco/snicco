<?php

declare(strict_types=1);

/**
 * @var ArrayAccess $test_context
 */

/** @psalm-suppress MixedArrayAccess */
echo (string) ($test_context['foo']['bar'] ?? 'Context foo.bar not set.');
