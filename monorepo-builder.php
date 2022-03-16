<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\ComposerJsonManipulator\ValueObject\ComposerJsonSection;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetCurrentMutualConflictsReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetCurrentMutualDependenciesReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\UpdateReplaceReleaseWorker;
use Symplify\MonorepoBuilder\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::DEFAULT_BRANCH_NAME, 'master');

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
            // These packages are needed during development in the monorepo but are not a dependency of any other package
            // (expect the testing packages).
            'phpunit/phpunit' => '9.5.13',
            'codeception/codeception' => '4.1.29',
            'symplify/monorepo-builder' => '9.4.70',
            'vlucas/phpdotenv' => '5.4.1',
            'lucatume/wp-browser' => '~3.1.4',
            'vimeo/psalm' => '^4.10',
            'rector/rector' => '^0.12.17',
            'symplify/easy-coding-standard' => '^10.1',
            'webmozart/assert' => '^1.10',
            'php-stubs/wordpress-stubs' => '^5.9.0',
            'symfony/finder' => '^5.0',
            'knplabs/github-api' => '^3.0',
            'guzzlehttp/guzzle' => '^7.0.1',
            'guzzlehttp/psr7' => '^2.1',
        ],
        ComposerJsonSection::AUTOLOAD_DEV => [
            'psr-4' => [
                'Snicco\\Monorepo\\' => 'src/Monorepo/',
                'Snicco\\Monorepo\\Tests\\' => 'tests/Monorepo/',
            ],
        ],
        'minimum-stability' => 'dev',
        'prefer-stable' => true,
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

    $services = $containerConfigurator->services();

    $services->set(UpdateReplaceReleaseWorker::class);
    $services->set(SetCurrentMutualConflictsReleaseWorker::class);
    $services->set(SetCurrentMutualDependenciesReleaseWorker::class);
};
