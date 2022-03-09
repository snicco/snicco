<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Serializer;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class JsonSerializer implements Serializer
{
    public function serialize(array $session_data): string
    {
        return json_encode($session_data, JSON_THROW_ON_ERROR);
    }

    public function deserialize(string $data): array
    {
        /** @var array $val */
        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }
}
