<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\SelfAccessorFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestCaseStaticMethodCallsFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
    ]);
    $parameters->set(Option::PARALLEL, true);

    $services = $containerConfigurator->services();

    $containerConfigurator->import(SetList::PHP_CS_FIXER_RISKY);
    $containerConfigurator->import(SetList::SPACES);
    $containerConfigurator->import(SetList::ARRAY);
    $containerConfigurator->import(SetList::DOCBLOCK);
    $containerConfigurator->import(SetList::CLEAN_CODE);
    $containerConfigurator->import(SetList::NAMESPACES);
    $containerConfigurator->import(SetList::STRICT);
    $containerConfigurator->import(SetList::PSR_12);
    $containerConfigurator->import(SetList::COMMENTS);
//    $containerConfigurator->import(SetList::PHP_CS_FIXER);

    // Dont rename test methods
    $services->remove(PhpUnitTestAnnotationFixer::class);
    // Import functions.
    $services->remove(NativeFunctionInvocationFixer::class);
    // This breaks assertions that compare object equality but not reference.
    $services->remove(PhpUnitStrictFixer::class);
    $services->remove(SelfAccessorFixer::class);

    $services->set(PhpUnitTestCaseStaticMethodCallsFixer::class)->call('configure', [
        ['call_type' => 'this']
    ]);

    // Allow @throws annotation.
    $services->set(GeneralPhpdocAnnotationRemoveFixer::class)->call('configure', [
            [
                'annotations' => ['author', 'package', 'group', 'covers', 'since']
            ]
        ]
    );

    $services->set(OrderedImportsFixer::class)->call('configure', [
        [
            'sort_algorithm' => 'alpha',
            'imports_order' => [
                'class',
                'function',
                'const',
            ]
        ]
    ]);
};
