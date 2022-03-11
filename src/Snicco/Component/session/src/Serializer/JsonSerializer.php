<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Serializer;

use InvalidArgumentException;

use function gettype;
use function is_array;
use function json_decode;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class JsonSerializer implements Serializer
{
    public function serialize(array $session_data): string
    {
        return json_encode($session_data, JSON_THROW_ON_ERROR);
    }

    public function deserialize(string $data): array
    {
        $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException(
                sprintf(__METHOD__ . " must return an array.\nGot [%s] for data [%s]", gettype($decoded), $data)
            );
        }

        return $decoded;
    }
}
