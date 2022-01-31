<?php

declare(strict_types=1);

namespace Snicco\Middleware\Payload;

use JsonException;
use Psr\Http\Message\StreamInterface;

use function sprintf;
use function json_decode;

use const JSON_THROW_ON_ERROR;
use const JSON_OBJECT_AS_ARRAY;

/**
 * @api
 */
final class JsonPayload extends Payload
{
    
    public function __construct()
    {
        parent::__construct(['application/json']);
    }
    
    protected function parse(StreamInterface $stream) :array
    {
        $json = trim((string) $stream);
        
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