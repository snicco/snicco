<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\Configuration;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Kernel\Configuration\ConfigLoader;
use Snicco\Component\Kernel\Tests\helpers\CreateTestContainer;
use Snicco\Component\Kernel\ValueObject\Directories;

use function dirname;

/**
 * @internal
 */
final class ConfigLoaderTest extends TestCase
{
    use CreateTestContainer;

    private string $fixtures_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures_dir = dirname(__DIR__) . '/fixtures';
    }

    /**
     * @test
     */
    public function all_files_in_the_config_directory_are_loaded_and_included_as_a_root_node_in_the_config(): void
    {
        $load_config = new ConfigLoader();
        $config = $load_config(Directories::fromDefaults($this->fixtures_dir)->configDir());

        $this->assertSame([
            'foo' => 'bar',
        ], $config['foo']);
        $this->assertSame([
            'foo' => 'baz',
        ], $config['custom-config']);
    }

    /**
     * @test
     */
    public function test_exception_if_config_file_does_not_return_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reading the [invalid] config did not return an array');

        $load_config = new ConfigLoader();
        $load_config($this->fixtures_dir . '/config_no_array_return/config');
    }
}
