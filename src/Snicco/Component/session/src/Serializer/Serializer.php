<?php

declare(strict_types=1);


namespace Snicco\Component\Session\Serializer;

interface Serializer
{
    public function serialize(array $session_data): string;

    public function deserialize(string $data): array;
}
