<?php

declare(strict_types=1);

namespace Snicco\Middleware\Payload;

use JsonException;
use Psr\Http\Message\StreamInterface;

use function json_decode;
use function sprintf;

use const JSON_OBJECT_AS_ARRAY;
use const JSON_THROW_ON_ERROR;

/**
 * @api
 */
final class JsonPayload extends Payload
{

    public function __construct()
    {
        parent::__construct(['application/json']);
    }

    protected function parse(StreamInterface $stream): array
    {
        $json = trim((string)$stream);

        if ($json === '') {
            return [];
        }
        try {
            return json_decode($json, true, 512, JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
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