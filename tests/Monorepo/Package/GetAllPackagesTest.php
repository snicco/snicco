<?php

declare(strict_types=1);

namespace Monorepo\Package;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Monorepo\Package\ComposerJson;
use Snicco\Monorepo\Package\Package;
use Snicco\Monorepo\Package\PackageCollection;
use Snicco\Monorepo\Package\PackageProvider;

use function dirname;

/**
 * @internal
 */
final class GetAllPackagesTest extends TestCase
{
    private string $valid_fixtures_dir;

    /**
     * @var string[]
     */
    private array $valid_package_dirs;

    /**
     * @var string[]
     */
    private array $package_dir_no_composer_json;

    /**
     * @var string[]
     */
    private array $package_dir_no_composer_name;

    protected function setUp(): void
    {
        parent::setUp();
        $this->valid_fixtures_dir = dirname(__DIR__, 3) . '/tests/Monorepo/fixtures';
        $this->valid_package_dirs = [
            dirname(__DIR__) . '/fixtures/packages/Component',
            dirname(__DIR__) . '/fixtures/packages/Bundle',
        ];
        $this->package_dir_no_composer_json = [dirname(__DIR__) . '/fixtures/wrong-packages/no-composer-json'];
        $this->package_dir_no_composer_name = [dirname(__DIR__) . '/fixtures/wrong-packages/no-composer-name'];
    }

    /**
     * @test
     */
    public function that_it_works_with_valid_composer_json_files(): void
    {
        $provider = new PackageProvider($this->valid_fixtures_dir, $this->valid_package_dirs);

        $packages = $provider->getAll();

        $this->assertCount(6, $packages);
        $this->assertEquals(
            new PackageCollection(
                [
                    new Package(
                        '/packages/Bundle/bundle-a',
                        $this->valid_fixtures_dir . '/packages/Bundle/bundle-a',
                        ComposerJson::for($this->valid_fixtures_dir . '/packages/Bundle/bundle-a/composer.json')
                    ),
                    new Package(
                        '/packages/Bundle/bundle-b',
                        $this->valid_fixtures_dir . '/packages/Bundle/bundle-b',
                        ComposerJson::for($this->valid_fixtures_dir . '/packages/Bundle/bundle-b/composer.json')
                    ),
                    new Package(
                        '/packages/Bundle/bundle-d',
                        $this->valid_fixtures_dir . '/packages/Bundle/bundle-d',
                        ComposerJson::for($this->valid_fixtures_dir . '/packages/Bundle/bundle-d/composer.json')
                    ),
                    new Package(
                        '/packages/Component/component-a',
                        $this->valid_fixtures_dir . '/packages/Component/component-a',
                        ComposerJson::for($this->valid_fixtures_dir . '/packages/Component/component-a/composer.json')
                    ),
                    new Package(
                        '/packages/Component/component-b',
                        $this->valid_fixtures_dir . '/packages/Component/component-b',
                        ComposerJson::for($this->valid_fixtures_dir . '/packages/Component/component-b/composer.json')
                    ),
                    new Package(
                        '/packages/Component/component-c',
                        $this->valid_fixtures_dir . '/packages/Component/component-c',
                        ComposerJson::for($this->valid_fixtures_dir . '/packages/Component/component-c/composer.json')
                    ),
                ]
            ),
            $packages
        );
    }

    /**
     * @test
     */
    public function that_it_throws_an_exception_if_no_composer_json_file_is_present(): void
    {
        $provider = new PackageProvider($this->valid_fixtures_dir, $this->package_dir_no_composer_json);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');
        $provider->getAll();
    }

    /**
     * @test
     */
    public function that_it_throws_an_exception_if_no_composer_json_name_is_set(): void
    {
        $provider = new PackageProvider($this->valid_fixtures_dir, $this->package_dir_no_composer_name);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('composer.json has no name');
        $provider->getAll();
    }
}
