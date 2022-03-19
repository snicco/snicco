<?php

declare(strict_types=1);

use Snicco\Component\StrArr\Str;
use Snicco\Monorepo\Package\PackageCollection;
use Snicco\Monorepo\SniccoWPPackageProvider;
use Webmozart\Assert\Assert;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

function fileLocation(): string
{
    return dirname(__DIR__, 2) . '/phpmetrics.json';
}

function generateGroups(PackageCollection $packages): array
{
    $components = [];
    $middleware = [];
    $bundles = [];
    $bridge = [];

    foreach ($packages as $package) {
        $dir = $package->package_dir_rel;

        $autoload = array_keys($package->composer_json->autoloadPsr4());
        Assert::true(isset($autoload[0]));
        $package_autoload = $autoload[0];
        Assert::stringNotEmpty($package_autoload);
        Assert::startsWith($package_autoload, 'Snicco');

        $parts = array_filter(explode('\\', $package_autoload));
        $short_name = end($parts);
        Assert::stringNotEmpty($short_name);

        $match = '!' . implode('\\\\', $parts) . '\\\\.*!';

        if (Str::startsWith($dir, 'src/Snicco/Middleware')) {
            $middleware[] = [
                'name' => $short_name,
                'match' => $match,
            ];
        } elseif (Str::startsWith($dir, 'src/Snicco/Component')) {
            $components[] = [
                'name' => $short_name,
                'match' => $match,
            ];
        } elseif (Str::startsWith($dir, 'src/Snicco/Bridge')) {
            $bridge[] = [
                'name' => $short_name . 'Bridge',
                'match' => $match,
            ];
        } elseif (Str::startsWith($dir, 'src/Snicco/Bundle')) {
            $bundles[] = [
                'name' => $short_name . 'Bundle',
                'match' => $match,
            ];
        } //  @noRector
        elseif (Str::startsWith($dir, 'src/Snicco/Testing')) {
            // skip
        } else {
            throw new InvalidArgumentException(sprintf('Invalid package [%s]', $package->name));
        }
    }

    sort($components);
    sort($bridge);
    sort($bundles);
    sort($middleware);

    return [...$components, ...$bridge, ...$bundles, ...$middleware];
}

try {
    $package_provider = SniccoWPPackageProvider::create();

    $groups = generateGroups($package_provider->getAll());

    $current_settings = json_decode(
        (string) file_get_contents(fileLocation()),
        true,
        JSON_THROW_ON_ERROR,
        JSON_THROW_ON_ERROR
    );
    Assert::isArray($current_settings);

    $current_settings['groups'] = $groups;

    $new_settings = json_encode($current_settings, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    $res = file_put_contents(fileLocation(), (string) $new_settings);
    Assert::notFalse($res, 'Could not update groups.');

    echo sprintf("Updated commit scopes at [%s]\n", fileLocation());
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}

exit(0);
