<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObject;

use Exception;
use InvalidArgumentException;
use LogicException;

use function bin2hex;
use function fopen;
use function fseek;
use function is_resource;
use function is_string;
use function stream_get_meta_data;

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
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 */
final class Attachment
{

    private string $encoding = 'base64';
    private string $content_type;
    private string $filename;
    private string $disposition;
    private string $cid;

    /**
     * @var resource|string
     */
    private $body;

    /**
     * @param resource|string $body
     * @psalm-suppress DocblockTypeContradiction
     * @throws Exception random bytes can't be generated for the content id
     */
    private function __construct($body, string $filename, string $content_type = null, bool $inline = false)
    {
        if (!is_string($body) && !is_resource($body)) {
            throw new InvalidArgumentException('The body of must be a string or a resource.');
        }

        $this->content_type = ($content_type === null) ? 'application/octet-stream' : $content_type;
        $this->filename = $filename;
        $this->disposition = $inline ? 'inline' : 'attachment';

        if ('inline' === $this->disposition) {
            $this->cid = bin2hex(random_bytes(16)) . '@sniccowp';
        }

        $this->body = $body;
    }

    public static function fromPath(
        string $path,
        string $name = null,
        string $content_type = null,
        bool $inline = false
    ): Attachment {
        if (!is_readable($path)) {
            throw new InvalidArgumentException(sprintf('Path "%s" is not readable.', $path));
        }
        $stream = @fopen($path, 'r');
        if (false === $stream) {
            throw new InvalidArgumentException(sprintf('Unable to open path "%s".', $path));
        }

        return new self($stream, $name ?? basename($path), $content_type, $inline);
    }

    /**
     * @param resource|string $data
     */
    public static function fromData(
        $data,
        string $filename,
        string $content_type = null,
        bool $inline = false
    ): Attachment {
        return new self($data, $filename, $content_type, $inline);
    }

    public function bodyAsString(): string
    {
        if (is_string($this->body)) {
            return $this->body;
        }

        $seekable = stream_get_meta_data($this->body)['seekable'] && fseek($this->body, 0, SEEK_CUR) === 0;

        if ($seekable) {
            rewind($this->body);
        }

        return stream_get_contents($this->body) ?: '';
    }

    public function name(): string
    {
        return $this->filename;
    }

    public function contentType(): string
    {
        return $this->content_type;
    }

    public function isInline(): bool
    {
        return 'inline' === $this->disposition();
    }

    public function disposition(): string
    {
        return $this->disposition;
    }

    public function encoding(): string
    {
        return $this->encoding;
    }

    public function cid(): string
    {
        if ('inline' !== $this->disposition) {
            throw new LogicException('Attachment is not embedded an has no cid.');
        }
        return $this->cid;
    }

    public function __destruct()
    {
        if (is_resource($this->body)) {
            fclose($this->body);
        }
    }

}