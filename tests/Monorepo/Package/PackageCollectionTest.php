<?php

declare(strict_types=1);

namespace Monorepo\Package;

use PHPUnit\Framework\TestCase;
use Snicco\Monorepo\Package\Package;
use Snicco\Monorepo\Package\PackageProvider;

use function dirname;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final class PackageCollectionTest extends TestCase
{
    private PackageProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new PackageProvider(
            dirname(__DIR__) . '/fixtures',
            [dirname(__DIR__) . '/fixtures/packages/Component', dirname(__DIR__) . '/fixtures/packages/Bundle']
        );
    }

    /**
     * @test
     */
    public function that_the_collection_can_converted_to_json(): void
    {
        $all = $this->provider->getAll();

        $json = json_encode($all, JSON_THROW_ON_ERROR);
        $this->assertIsArray($arr = json_decode($json, true, 512, JSON_THROW_ON_ERROR));
        $this->assertCount(6, $all);

        foreach ($arr as $key => $package) {
            $this->assertIsInt($key);
            $this->assertIsArray($package);
            $this->assertArrayHasKey(Package::VENDOR_NAME, $package);
            $this->assertArrayHasKey(Package::NAME, $package);
            $this->assertArrayHasKey(Package::COMPOSER_JSON_PATH, $package);
            $this->assertArrayHasKey(Package::ABSOLUTE_PATH, $package);
            $this->assertArrayHasKey(Package::RELATIVE_PATH, $package);
        }
    }

    /**
     * @test
     */
    public function that_one_package_can_be_filtered_out(): void
    {
        $all = $this->provider->getAll();

        $package = $all->get('snicco/component-a-name');

        $this->assertSame('snicco/component-a-name', $package->full_name);

        $this->expectExceptionMessage('The package [snicco/bogus] is not in the collection.');
        $all->get('snicco/bogus');
    }
}
