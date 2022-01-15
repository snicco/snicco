<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use RuntimeException;
use Psr\Http\Message\StreamInterface;

/**
 * @api
 */
final class JsonPayload extends Payload
{
    
    public function __construct($content_types = ['application/json'])
    {
        parent::__construct($content_types);
    }
    
    protected function parse(StreamInterface $stream) :array
    {
        $json = trim((string) $stream);
        
        if ($json === '') {
            return [];
        }
        
        return $this->decode($json);
    }
    
    private function decode(string $json)
    {
        $data = json_decode($json, true, 512, JSON_OBJECT_AS_ARRAY);
        $code = json_last_error();
        
        if ($code !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                sprintf('JSON: %s. Payload: %s.', json_last_error_msg(), $json)
            );
        }
        
        return $data;
    }
    
}