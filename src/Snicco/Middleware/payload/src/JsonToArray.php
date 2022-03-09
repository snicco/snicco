<?php

declare(strict_types=1);

namespace Snicco\Middleware\Payload;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Throwable;

use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class JsonToArray extends Payload
{
    public function __construct()
    {
        parent::__construct(['application/json']);
    }

    /**
     * @return array<string,mixed>
     */
    protected function parse(StreamInterface $stream): array
    {
        $json = trim((string) $stream);

        if ('' === $json) {
            return [];
        }

        try {
            $res = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($res)) {
                throw new InvalidArgumentException('json_decoding the request body did not return an array.');
            }

            foreach (array_keys($res) as $key) {
                if (! is_string($key)) {
                    throw new InvalidArgumentException(
                        'json_decoding the request body must return an array keyed by strings.'
                    );
                }
            }
            /** @psalm-var array<string,mixed> */
            return $res;
        } catch (Throwable $e) {
            throw new CantParseRequestBody(
                sprintf(
                    "Cant decode json body [%s].\n[%s]",
                    $json,
                    $e->getMessage()
                ),
                $e
            );
        }
    }
}
