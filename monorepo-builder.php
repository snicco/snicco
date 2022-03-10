<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\ComposerJsonManipulator\ValueObject\ComposerJsonSection;
use Symplify\MonorepoBuilder\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::PACKAGE_DIRECTORIES, [
        __DIR__ . '/src/Snicco/Component',
        __DIR__ . '/src/Snicco/Bridge',
        __DIR__ . '/src/Snicco/Middleware',
        __DIR__ . '/src/Snicco/Bundle',
    ]);

    $parameters->set(Option::DATA_TO_APPEND, [
        ComposerJsonSection::REQUIRE => [
            'php' => '^7.4|^8.0',
        ],
        ComposerJsonSection::REQUIRE_DEV => [
            'phpunit/phpunit' => '9.5.13',
            'codeception/codeception' => '4.1.29',
            'symplify/monorepo-builder' => '9.4.70',
            'vlucas/phpdotenv' => '5.4.1',
            'lucatume/wp-browser' => '~3.1.4',
            'vimeo/psalm' => '^4.10',
            'php-stubs/wordpress-stubs' => '^5.8.0',
        ],
        ComposerJsonSection::AUTHORS => [
            [
                'name' => 'Calvin Alkan',
                'email' => 'calvin@snicco.de',
            ],
        ],
        ComposerJsonSection::CONFIG => [
            'optimize-autoloader' => true,
            'preferred-install' => 'dist',
            'sort-packages' => true,
        ],
        ComposerJsonSection::MINIMUM_STABILITY => 'dev',
    ]);

    $parameters->set(Option::DATA_TO_REMOVE, [
        ComposerJsonSection::REQUIRE => [
            'sniccowp/http-routing' => '*',
            'sniccowp/psr7-error-handler' => '*',
            'sniccowp/pimple-bridge' => '*',
        ],
        ComposerJsonSection::REQUIRE_DEV => [
            // temporary until split
            'sniccowp/pimple-bridge' => '*',
        ],
    ]);
};
