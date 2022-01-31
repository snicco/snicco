<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObjects;

use InvalidArgumentException;

use function is_resource;

use const SEEK_CUR;

/*
 * Slight modified version of the symfony/mime, DataPart class
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
    
    private string $encoding = 'base64';
    private string $content_type;
    private bool   $seekable;
    private string $filename;
    private string $disposition;
    private string $cid;
    
    /**
     * @var resource|string
     */
    private $body;
    
    /**
     * @param  resource|string  $body
     */
    private function __construct($body, string $filename, string $content_type = null, bool $inline = false)
    {
        if ( ! is_string($body) && ! is_resource($body)) {
            throw new InvalidArgumentException('The body of must be a string or a resource.');
        }
        
        $this->content_type = ($content_type === null) ? 'application/octet-stream' : $content_type;
        
        if ( ! is_resource($body)) {
            $this->seekable = false;
        }
        else {
            $this->seekable = stream_get_meta_data($body)['seekable']
                              && fseek($body, 0, SEEK_CUR) === 0;
        }
        
        $this->filename = $filename;
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
    
    public function body() :string
    {
        if ( ! $this->seekable) {
            return $this->body;
        }
        
        rewind($this->body);
        
        return stream_get_contents($this->body) ? : '';
    }
    
    public function disposition() :string
    {
        return $this->disposition;
    }
    
    public function name() :string
    {
        return $this->filename;
    }
    
    public function contentType() :string
    {
        return $this->content_type;
    }
    
    public function isInline() :bool
    {
        return 'inline' === $this->disposition();
    }
    
    public function encoding() :string
    {
        return $this->encoding;
    }
    
    public function cid() :string
    {
        return $this->cid ??= $this->generateContentId();
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