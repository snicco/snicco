<?php

declare(strict_types=1);


namespace Monorepo\Package;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Monorepo\Package\PackageProvider;

use function dirname;

final class GetPackageTest extends TestCase
{

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
    public function that_a_single_package_can_be_created_by_its_composer_path(): void
    {
        $package = $this->package_provider->get($this->fixtures_dir . '/packages/Component/component-a/composer.json');
        $this->assertSame(
            $this->fixtures_dir . '/packages/Component/component-a/composer.json',
            $package->composer_json->realPath()
        );
        $this->assertSame('packages/Component/component-a', $package->package_dir_rel);
        $this->assertSame($this->fixtures_dir . '/packages/Component/component-a', $package->package_dir_abs);
        $this->assertSame('component-a-name', $package->name);


        $package = $this->package_provider->get('packages/Component/component-a/composer.json');
        $this->assertSame(
            $this->fixtures_dir . '/packages/Component/component-a/composer.json',
            $package->composer_json->realPath()
        );
        $this->assertSame('packages/Component/component-a', $package->package_dir_rel);
        $this->assertSame($this->fixtures_dir . '/packages/Component/component-a', $package->package_dir_abs);
        $this->assertSame('component-a-name', $package->name);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Non-existent file');
        $this->package_provider->get($this->fixtures_dir . '/packages/Component/component-bogus/composer.json');
    }

}