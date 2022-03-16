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
use Rector\PHPUnit\Rector\Class_\AddSeeTestAnnotationRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector;
use Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector;
use Rector\Privatization\Rector\MethodCall\PrivatizeLocalGetterToPropertyRector;
use Rector\Privatization\Rector\Property\ChangeReadOnlyPropertyWithDefaultValueToConstantRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddArrayReturnDocTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;
use Snicco\Bridge\SessionWP\WPDBSessionDriver;
use Snicco\Bundle\HttpRouting\StdErrLogger;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\BetterWPCache\Tests\wordpress\TaggingIntegrationTest;
use Snicco\Component\BetterWPCache\Tests\wordpress\WPObjectCachePsr16IntegrationTest;
use Snicco\Component\BetterWPCache\Tests\wordpress\WPObjectCachePsr6IntegrationTest;
use Snicco\Component\BetterWPCache\WPCacheAPI;
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

if (! function_exists('_classFile')) {
    /**
     * @param class-string $class_name
     *
     * @throws ReflectionException
     */
    function _classFile(string $class_name): string
    {
        return (new ReflectionClass($class_name))->getFileName();
    }
}

return static function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/monorepo-builder.php',
        __DIR__ . '/tests',
        __DIR__ . '/ecs.php',
        __DIR__ . '/rector.php',
        __DIR__ . '/bin/php',
    ]);
    $parameters->set(Option::PARALLEL, true);
    $parameters->set(Option::PHP_VERSION_FEATURES, '7.4');
    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    $parameters->set(Option::IMPORT_SHORT_CLASSES, true);
    $parameters->set(Option::SKIP, [
        StringifyStrNeedlesRector::class => [_classFile(StdErrLogger::class)],
        EncapsedStringsToSprintfRector::class => [_classFile(WPDBSessionDriver::class)],
        // This is not our code
        _classFile(WPObjectCachePsr16IntegrationTest::class),
        _classFile(WPObjectCachePsr6IntegrationTest::class),
        _classFile(TaggingIntegrationTest::class),

        FinalizeClassesWithoutChildrenRector::class => [
            // This does not work correctly with classes in different sub-namespaces
            _classFile(Email::class),
            _classFile(WPCacheAPI::class),
            _classFile(HttpException::class),
            _classFile(BetterWPAPI::class),
        ],
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

    $configurator->import(PHPUnitSetList::PHPUNIT_CODE_QUALITY);
    // Useless. PHPStorms supports this out of the box.
    $services->remove(AddSeeTestAnnotationRector::class);

    // Parts to the SetList::PRIVATIZATION list
    $services->set(FinalizeClassesWithoutChildrenRector::class);
    $services->set(ChangeReadOnlyPropertyWithDefaultValueToConstantRector::class);
    $services->set(PrivatizeLocalGetterToPropertyRector::class);
    $services->set(PrivatizeFinalClassPropertyRector::class);
    $services->set(PrivatizeFinalClassMethodRector::class);
};
