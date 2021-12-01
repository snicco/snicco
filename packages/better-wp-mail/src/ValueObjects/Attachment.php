<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

use TypeError;
use InvalidArgumentException;

use function is_resource;

use const SEEK_CUR;

/*
 * Slight modified version of the symfony/mimi DataPart class
 * https://github.com/symfony/symfony/blob/5.3/src/Symfony/Component/Mime/Part/DataPart.php
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * License: MIT, https://github.com/symfony/mime/blob/5.3/LICENSE
 */

/**
 * @api
 */
final class Attachment
{
    
    /**
     * @var string
     */
    private $encoding = 'base64';
    
    /**
     * @var string
     */
    private $content_type;
    
    /**
     * @var bool
     */
    private $seekable;
    
    /**
     * @var string
     */
    private $filename;
    
    /**
     * @var string
     */
    private $disposition;
    
    /**
     * @var resource|string
     */
    private $body;
    
    /**
     * @var string
     */
    private $cid;
    
    /**
     * @param  resource|string  $body
     */
    private function __construct($body, string $filename = null, string $content_type = null, bool $inline = false)
    {
        if ( ! is_string($body) && ! is_resource($body)) {
            throw new TypeError('The body of must be a string or a resource.');
        }
        
        $this->content_type = ($content_type === null) ? 'application/octet-stream' : $content_type;
        
        if ( ! is_resource($body)) {
            $this->seekable = false;
        }
        else {
            $this->seekable = stream_get_meta_data($body)['seekable']
                              && fseek($body, 0, SEEK_CUR) === 0;
        }
        
        if ($filename) {
            $this->filename = $filename;
        }
        
        $this->disposition = $inline ? 'inline' : 'attachment';
        $this->body = $body;
    }
    
    public static function fromPath(string $path, string $name = null, string $content_type = null, bool $inline = false) :Attachment
    {
        if ( ! is_readable($path)) {
            throw new InvalidArgumentException(sprintf('Path "%s" is not readable.', $path));
        }
        
        if (false === $stream = @fopen($path, 'r')) {
            throw new InvalidArgumentException(sprintf('Unable to open path "%s".', $path));
        }
        
        return new self($stream, $name ?? basename($path), $content_type, $inline);
    }
    
    /**
     * @param  resource|string  $data
     */
    public static function fromData($data, string $filename, string $content_type = null, bool $inline = false) :Attachment
    {
        return new self($data, $filename, $content_type, $inline);
    }
    
    public function getBody() :string
    {
        if ( ! $this->seekable) {
            return $this->body;
        }
        
        rewind($this->body);
        
        return stream_get_contents($this->body) ? : '';
    }
    
    public function getDisposition() :string
    {
        return $this->disposition;
    }
    
    public function getName() :?string
    {
        return $this->filename;
    }
    
    public function getContentType() :string
    {
        return $this->content_type;
    }
    
    public function getEncoding() :string
    {
        return $this->encoding;
    }
    
    public function getContentId() :string
    {
        return $this->cid ? : $this->cid = $this->generateContentId();
    }
    
    public function __destruct()
    {
        if ($this->body && is_resource($this->body)) {
            fclose($this->body);
        }
    }
    
    private function generateContentId() :string
    {
        return bin2hex(random_bytes(16)).'@sniccwp';
    }
    
}