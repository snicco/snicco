<?php

declare(strict_types=1);

use Snicco\Monorepo\Input;
use Snicco\Monorepo\Packagist\AlreadyAtPackagist;
use Snicco\Monorepo\Packagist\CreatePackage;
use Snicco\Monorepo\SniccoWPPackageProvider;

require_once dirname(__DIR__) . '/vendor/autoload.php';

try {
    $input = new Input($argv);

    $composer_json = rtrim($input->mainArg(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.json';
    $package = SniccoWPPackageProvider::create()->get($composer_json);

    $api_token = $input->parse('--token');
    $user_name = $input->parse('--u');

    $create_package = new CreatePackage($user_name, $api_token);

    echo "Pushing package [{$package->full_name}] to https://packagist.org.\n";

    try {
        $url = $create_package($package);
        echo "Pushed package [{$package->full_name}] to {$url}.\n";
    } catch (AlreadyAtPackagist $e) {
        echo $e->getMessage() . "\n";
    }
} catch (Throwable $e) {
    echo sprintf("[%s] %d - %s\n", get_class($e), $e->getCode(), $e->getMessage());
    exit(1);
}

exit(0);
