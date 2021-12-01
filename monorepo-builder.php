<?php

declare(strict_types=1);

use Symplify\MonorepoBuilder\ValueObject\Option;
use Symplify\ComposerJsonManipulator\ValueObject\ComposerJsonSection;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator) :void {
    $parameters = $containerConfigurator->parameters();
    //for "merge" command
    $parameters->set(Option::DATA_TO_APPEND, [
        ComposerJsonSection::REQUIRE_DEV => [
            'lucatume/wp-browser' => '^3.0.0',
            'phpunit/phpunit' => '^9.5',
            'mockery/mockery' => '^1.4.2',
            'symplify/monorepo-builder' => '^9.4',
        ],
        ComposerJsonSection::AUTOLOAD_DEV => [
            'psr-4' => [
                "Tests\\Codeception\\" => 'codeception',
            ],
        ],
    ]);
};
