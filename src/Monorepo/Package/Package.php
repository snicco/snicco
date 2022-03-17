<?php

declare(strict_types=1);

namespace Snicco\Monorepo\Package;

use JsonSerializable;
use Snicco\Component\StrArr\Str;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_keys;
use function array_values;
use function explode;
use function is_file;

/**
 * @psalm-immutable
 */
final class Package implements JsonSerializable
{
    /**
     * @var string
     */
    public const VENDOR_NAME = 'vendor_name';

    /**
     * @var string
     */
    public const NAME = 'name';

    /**
     * @var string
     */
    public const FULL_NAME = 'full_name';

    /**
     * @var string
     */
    public const RELATIVE_PATH = 'relative_path';

    /**
     * @var string
     */
    public const ABSOLUTE_PATH = 'absolute_path';

    /**
     * @var string
     */
    public const COMPOSER_JSON_PATH = 'composer_json_path';

    /**
     * @var non-empty-string
     */
    public string $name;

    /**
     * @var non-empty-string
     */
    public string $vendor_name;

    /**
     * @var non-empty-string
     */
    public string $full_name;

    /**
     * @var non-empty-string
     */
    public string $package_dir_rel;

    /**
     * @var non-empty-string
     */
    public string $package_dir_abs;

    public string $description;

    public ComposerJson $composer_json;

    public function __construct(string $package_dir_rel, string $package_dir_abs, ComposerJson $composer_json)
    {
        Assert::stringNotEmpty($package_dir_rel);
        Assert::stringNotEmpty($package_dir_abs);
        $parts = $this->parsePackageName($composer_json);

        $this->vendor_name = $parts[0];
        $this->name = $parts[1];
        /** @var non-empty-string $full_name */
        $full_name = sprintf('%s/%s', $this->vendor_name, $this->name);
        $this->full_name = $full_name;

        $this->package_dir_rel = $package_dir_rel;
        $this->package_dir_abs = $package_dir_abs;
        $this->composer_json = $composer_json;
        $this->description = $this->composer_json->description();
    }

    /**
     * @return list<string>
     */
    public function firstPartyDependencyNames(): array
    {
        $all = array_keys($this->composer_json->allRequired());

        /** @psalm-suppress ImpureFunctionCall */
        return array_values(array_filter($all, fn(string $name): bool => Str::startsWith($name, $this->vendor_name)));
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array{
     *     name: string,
     *     vendor_name:string,
     *     full_name:string,
     *     composer_json_path: string,
     *     relative_path: string,
     *     absolute_path:string
     * }
     */
    public function toArray(): array
    {
        return [
            self::NAME => $this->name,
            self::VENDOR_NAME => $this->vendor_name,
            self::FULL_NAME => $this->full_name,
            self::ABSOLUTE_PATH => $this->package_dir_abs,
            self::RELATIVE_PATH => $this->package_dir_rel,
            self::COMPOSER_JSON_PATH => $this->composer_json->realPath(),
        ];
    }

    public function usesPHPUnit(): bool
    {
        /** @psalm-suppress ImpureFunctionCall file_exists is pure */
        return is_file($this->package_dir_rel . '/phpunit.xml.dist');
    }

    public function usesCodeception(): bool
    {
        /** @psalm-suppress ImpureFunctionCall file_exists is pure */
        return is_file($this->package_dir_rel . '/codeception.dist.yml');
    }

    /**
     * @return array{0:non-empty-string, 1:non-empty-string}
     */
    private function parsePackageName(ComposerJson $composer_json): array
    {
        $parts = explode('/', $composer_json->name());
        Assert::true(isset($parts[0], $parts[1]));
        Assert::stringNotEmpty($parts[0]);
        Assert::stringNotEmpty($parts[1]);

        return [$parts[0], $parts[1]];
    }
}
