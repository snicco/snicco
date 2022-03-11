<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Array_\ArrayThisCallToThisMethodCallRector;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Class_\AddArrayDefaultToArrayPropertyRector;
use Rector\CodingStyle\Rector\ClassMethod\UnSpreadOperatorRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Core\Configuration\Option;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector;
use Rector\Php73\Rector\FuncCall\StringifyStrNeedlesRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddArrayReturnDocTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/monorepo-builder.php',
        __DIR__ . '/tests',
        __DIR__ . '/ecs.php',
        __DIR__ . '/rector.php',
    ]);
    $parameters->set(Option::PARALLEL, true);
    $parameters->set(Option::PHP_VERSION_FEATURES, '7.4');
    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    $parameters->set(Option::IMPORT_SHORT_CLASSES, true);
    $parameters->set(Option::SKIP, [
        StringifyStrNeedlesRector::class => [__DIR__ . '/src/Snicco/Bundle/http-routing/src/StdErrLogger.php'],
        EncapsedStringsToSprintfRector::class => [
            __DIR__ . '/src/Snicco/Bridge/session-wp/src/WPDBSessionDriver.php',
        ],
        // This is not our code
        __DIR__ . '/src/Snicco/Component/better-wp-cache/tests/wordpress/WPObjectCachePsr16IntegrationTest.php',
        __DIR__ . '/src/Snicco/Component/better-wp-cache/tests/wordpress/WPObjectCachePsr6IntegrationTest.php',
        __DIR__ . '/src/Snicco/Component/better-wp-cache/tests/wordpress/TaggingIntegrationTest.php',
    ]);

    $services = $configurator->services();

    // This list can be imported as is.
    $configurator->import(LevelSetList::UP_TO_PHP_74);

    $configurator->import(SetList::CODE_QUALITY);
    // Will break everywhere Controller are used.
    $services->remove(CallableThisArrayToAnonymousFunctionRector::class);
    $services->remove(ArrayThisCallToThisMethodCallRector::class);

    $configurator->import(SetList::TYPE_DECLARATION);
    // This changes doc-blocks based on inferred calls to the method.
    $services->remove(ParamTypeByMethodCallTypeRector::class);
    // This causes a lot of trouble with psalm and classes that implement an interface.
    // Maybe revisit later.
    $services->remove(AddArrayReturnDocTypeRector::class);

    $configurator->import(SetList::TYPE_DECLARATION_STRICT);

    $configurator->import(SetList::CODING_STYLE);
    // Don't want this since it only support kebabCase
    $services->remove(CatchExceptionNameMatchingTypeRector::class);
    // Break classes like ViewEngine where we rely on ... for type-checks
    $services->remove(UnSpreadOperatorRector::class);
    // Breaks typed array properties in psalm
    $services->remove(AddArrayDefaultToArrayPropertyRector::class);

    $configurator->import(SetList::EARLY_RETURN);

    $configurator->import(SetList::DEAD_CODE);
    // Breaks psalm with static and self.
    $services->remove(RemoveUselessReturnTagRector::class);
    // Does not play nicely with psalm and (string) casts
    $services->remove(RecastingRemovalRector::class);
    $services->remove(RemoveConcatAutocastRector::class);
};
