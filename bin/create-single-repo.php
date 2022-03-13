<?php

declare(strict_types=1);

use Snicco\Monorepo\GitHub\AlreadyARepository;
use Snicco\Monorepo\GitHub\CreateRepository;
use Snicco\Monorepo\Input;
use Snicco\Monorepo\SniccoWPPackageProvider;

require_once dirname(__DIR__) . '/vendor/autoload.php';

try {
    $input = new Input($argv);

    $composer_json = rtrim($input->mainArg(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.json';
    $package = SniccoWPPackageProvider::create()->get($composer_json);

    $api_token = $input->parse('--token');

    $create_repository = new CreateRepository($api_token);

    echo "Creating GitHub Repo for package [$package->full_name].\n";

    try {
        $url = $create_repository($package);
        echo "Created GitHub Repo for package [$package->full_name] at [$url].\n";
    } catch (AlreadyARepository $e) {
        echo $e->getMessage() . "\n";
    }
} catch (Throwable $e) {
    echo sprintf("[%s] %d - %s\n", get_class($e), $e->getCode(), $e->getMessage());
    exit(1);
}
exit(0);