<?php

declare(strict_types=1);

namespace Snicco\Monorepo\Tests\Package;

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
final class GetAffectedPackagesTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private string $fixtures_dir;

    private PackageProvider $package_provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures_dir = dirname(__DIR__) . '/fixtures';
        $this->package_provider = new PackageProvider($this->fixtures_dir, [
            $this->fixtures_dir . '/packages/Component',
            $this->fixtures_dir . '/packages/Bundle',
        ]);
    }

    /**
     * @test
     */
    public function that_it_returns_an_collection_for_an_empty_input(): void
    {
        $packages = $this->package_provider->getAffected([]);
        $this->assertEquals(new PackageCollection([]), $packages);
    }

    /**
     * @test
     */
    public function that_it_returns_an_empty_collection_for_changed_files_not_in_packages_folder(): void
    {
        $packages = $this->package_provider->getAffected([dirname(__DIR__) . '/fixtures/foo.php']);
        $this->assertEquals(new PackageCollection([]), $packages);

        $packages = $this->package_provider->getAffected(['/foo.php']);
        $this->assertEquals(new PackageCollection([]), $packages);
    }

    /**
     * @test
     */
    public function that_a_package_is_marked_as_affected_if_at_least_one_file_in_the_package_was_changed(): void
    {
        $packages = $this->package_provider->getAffected(
            [dirname(__DIR__) . '/fixtures/packages/Bundle/bundle-a/bundle-a.php']
        );

        $this->assertCount(1, $packages);
        $this->assertTrue($packages->contains('sniccowp/bundle-a-name'));

        $packages = $this->package_provider->getAffected(['/packages/Bundle/bundle-a/bundle-a.php']);

        $this->assertCount(1, $packages);
        $this->assertTrue($packages->contains('sniccowp/bundle-a-name'));
    }

    /**
     * @test
     */
    public function that_dependencies_are_resolved_from_composer_json_and_added_to_the_list_of_affected_packages(): void
    {
        $packages = $this->package_provider->getAffected(
            [dirname(__DIR__) . '/fixtures/packages/Component/component-a/component-a.php']
        );

        $this->assertCount(2, $packages);
        $this->assertTrue($packages->contains('sniccowp/bundle-a-name'));
        $this->assertTrue($packages->contains('sniccowp/component-a-name'));

        $packages = $this->package_provider->getAffected(['/packages/Component/component-a/component-a.php']);

        $this->assertCount(2, $packages);
        $this->assertTrue($packages->contains('sniccowp/bundle-a-name'));
        $this->assertTrue($packages->contains('sniccowp/component-a-name'));
    }

    /**
     * @test
     */
    public function that_dependencies_are_resolved_from_composer_json_recursively(): void
    {
        $packages = $this->package_provider->getAffected(
            [dirname(__DIR__) . '/fixtures/packages/Component/component-b/component-b.php']
        );

        $expected = new PackageCollection([
            new Package(
                'packages/Bundle/bundle-a',
                $this->fixtures_dir . '/packages/Bundle/bundle-a',
                ComposerJson::for($this->fixtures_dir . '/packages/Bundle/bundle-a/composer.json')
            ),
            new Package(
                'packages/Bundle/bundle-b',
                $this->fixtures_dir . '/packages/Bundle/bundle-b',
                ComposerJson::for($this->fixtures_dir . '/packages/Bundle/bundle-b/composer.json')
            ),
            new Package(
                'packages/Component/component-a',
                $this->fixtures_dir . '/packages/Component/component-a',
                ComposerJson::for($this->fixtures_dir . '/packages/Component/component-a/composer.json')
            ),
            new Package(
                'packages/Component/component-b',
                $this->fixtures_dir . '/packages/Component/component-b',
                ComposerJson::for($this->fixtures_dir . '/packages/Component/component-b/composer.json')
            ),
        ]);

        $this->assertEquals($expected, $packages);

        $packages = $this->package_provider->getAffected(['/packages/Component/component-b/component-b.php']);
        $this->assertEquals($expected, $packages);
    }

    /**
     * @test
     */
    public function that_recursion_is_detected(): void
    {
        $provider = new PackageProvider($this->fixtures_dir, [$this->fixtures_dir . '/wrong-packages/recursion']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Package [sniccowp/package-c] requires a package that itself requires package [sniccowp/package-c] somewhere in the dependency chain.'
        );
        $provider->getAffected(['/wrong-packages/recursion/package-c/package-c.php']);
    }
}
