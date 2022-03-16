<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Information;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Identifier\ExceptionIdentifier;
use Snicco\Component\Psr7ErrorHandler\UserFacing;
use Throwable;

use function dirname;
use function file_get_contents;
use function is_string;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

final class InformationProviderWithTransformation implements ExceptionInformationProvider
{
    private ExceptionIdentifier $identifier;

    /**
     * @var array<positive-int,array{title:string, message:string}>
     */
    private array $default_messages = [];

    /**
     * @var ExceptionTransformer[]
     */
    private array $transformer = [];

    /**
     * @param array<positive-int,array{title:string, message:string}> $data
     */
    public function __construct(array $data, ExceptionIdentifier $identifier, ExceptionTransformer ...$transformer)
    {
        foreach ($data as $status_code => $title_and_details) {
            $this->addMessage($status_code, $title_and_details);
        }

        if (!isset($this->default_messages[500])) {
            throw new InvalidArgumentException('Data for the 500 status code must be provided.');
        }

        $this->transformer = $transformer;
        $this->identifier = $identifier;
    }

    public static function fromDefaultData(ExceptionIdentifier $identifier, ExceptionTransformer ...$transformer): self
    {
        $data = file_get_contents($f = dirname(__DIR__, 2) . '/resources/en_US.error.json');

        if (false === $data) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf('Cant read file contents of file [%s]', $f));
            // @codeCoverageIgnoreEnd
        }

        /** @var array<positive-int,array{title:string, message:string}> $decoded */
        $decoded = json_decode($data, true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);

        return new self($decoded, $identifier, ...$transformer);
    }

    public function createFor(Throwable $e, ServerRequestInterface $request): ExceptionInformation
    {
        $original = $e;
        $transformed = $this->transform($original);

        $status = $transformed instanceof HttpException ? $transformed->statusCode() : 500;

        [$title, $details] = $this->getData($status, $transformed, $original);

        return new ExceptionInformation(
            $status,
            $this->identifier->identify($original),
            $title,
            $details,
            $original,
            $transformed,
            $request
        );
    }

    /**
     * @param array{title: string, message:string} $info
     */
    private function addMessage(int $status_code, array $info): void
    {
        if ($status_code < 400) {
            throw new InvalidArgumentException('$status_code must be greater >= 400.');
        }

        /**
         * @var positive-int $status_code
         * @psalm-suppress DocblockTypeContradiction
         */
        if (!isset($info['title']) || !is_string($info['title'])) {
            throw new InvalidArgumentException(sprintf('$title must be string for status code [%d].', $status_code));
        }

        /**
         * @psalm-suppress DocblockTypeContradiction
         */
        if (!isset($info['message']) || !is_string($info['message'])) {
            throw new InvalidArgumentException(sprintf('$message must be string for status code [%d].', $status_code));
        }

        $this->default_messages[$status_code] = $info;
    }

    private function transform(Throwable $throwable): Throwable
    {
        $transformed = $throwable;

        foreach ($this->transformer as $transformer) {
            $transformed = $transformer->transform($transformed);
        }

        return $transformed;
    }

    /**
     * @return array{0:string, 1:string}
     */
    private function getData(int $status_code, Throwable $transformed, Throwable $original): array
    {
        /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
        $info = $this->default_messages[$status_code] ?? $this->default_messages[500];
        $title = $info['title'];
        $safe_message = $info['message'];

        if ($original instanceof UserFacing) {
            $title = $original->title();
            $safe_message = $original->safeMessage();
        }

        if ($transformed instanceof UserFacing) {
            $title = $transformed->title();
            $safe_message = $transformed->safeMessage();
        }

        return [$title, $safe_message];
    }
}
