<?php

declare(strict_types=1);

namespace Snicco\Monorepo\Package;

use JsonException;
use RuntimeException;
use Webmozart\Assert\Assert;

use function array_keys;
use function array_merge;
use function file_get_contents;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-immutable
 */
final class ComposerJson
{
    /**
     * @var string
     */
    private const NAME = 'name';

    /**
     * @var string
     */
    private const REQUIRE = 'require';

    /**
     * @var string
     */
    private const REQUIRE_DEV = 'require-dev';

    /**
     * @var string
     */
    private const DESCRIPTION = 'description';

    private array $composer_json_contents;

    private string $file;

    /**
     * @param array<string,mixed> $composer_json_contents
     */
    private function __construct(array $composer_json_contents, string $file)
    {
        $this->composer_json_contents = $composer_json_contents;
        $this->file = $file;
    }

    /**
     * @throws JsonException
     */
    public static function for(string $composer_json_file): self
    {
        Assert::file($composer_json_file);
        Assert::readable($composer_json_file);

        $contents = file_get_contents($composer_json_file);
        if (false === $contents) {
            throw new RuntimeException(sprintf('Could not read contents of file [%s]', $composer_json_file));
        }

        $content = (array) json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        /**
         * @psalm-var array<string,mixed> $content
         */
        Assert::allString(array_keys($content));

        return new self($content, $composer_json_file);
    }

    /**
     * @return array<string,string>
     */
    public function require(): array
    {
        $require = (array) ($this->composer_json_contents[self::REQUIRE] ?? []);
        Assert::allString($require);
        Assert::allString(array_keys($require));

        /**
         * @psalm-var array<string,string> $require
         */

        return $require;
    }

    /**
     * @return array<string,string>
     */
    public function requireDev(): array
    {
        $require = (array) ($this->composer_json_contents[self::REQUIRE_DEV] ?? []);
        Assert::allString($require);
        Assert::allString(array_keys($require));

        /**
         * @psalm-var array<string,string> $require
         */

        return $require;
    }

    /**
     * @return array<string,string>
     */
    public function allRequired(): array
    {
        return array_merge($this->require(), $this->requireDev());
    }

    /**
     * @psalm-return non-empty-string
     */
    public function name(): string
    {
        Assert::true(
            isset($this->composer_json_contents[self::NAME]),
            sprintf('composer.json has no name [%s].', $this->file)
        );
        Assert::stringNotEmpty($this->composer_json_contents[self::NAME]);

        return $this->composer_json_contents[self::NAME];
    }

    public function realPath(): string
    {
        return $this->file;
    }

    public function description(): string
    {
        $description = $this->composer_json_contents[self::DESCRIPTION] ?? '';
        Assert::string($description);

        return $description;
    }

    public function contents(): array
    {
        return $this->composer_json_contents;
    }
}
