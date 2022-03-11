<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Alias\NoMixedEchoPrintFixer;
use PhpCsFixer\Fixer\ClassNotation\SelfAccessorFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoUselessInheritdocFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestCaseStaticMethodCallsFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestClassRequiresCoversFixer;
use PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $configurator): void {
    $parameters = $configurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/monorepo-builder.php',
        __DIR__ . '/tests',
        __DIR__ . '/ecs.php',
    ]);
    $parameters->set(Option::PARALLEL, true);

    $services = $configurator->services();

    // Import base rules.
    $configurator->import(SetList::PHP_CS_FIXER);

    // We don't use @covers annotations.
    $services->remove(PhpUnitTestClassRequiresCoversFixer::class);

    // Don't turn inline psalm annotations to comments.
    // @see https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/4446
    $services->set(PhpdocToCommentFixer::class)->call('configure', [
        [
            'ignored_tags' => ['psalm-suppress', 'var', 'psalm-var'],
        ],
    ]);

    $services->set(MultilineWhitespaceBeforeSemicolonsFixer::class)->call('configure', [
        [
            'strategy' => 'no_multi_line',
        ],
    ]);

    // PHPUnit test methods must be snake_case
    $services->set(PhpUnitMethodCasingFixer::class)->call('configure', [
        [
            'case' => 'snake_case',
        ],
    ]);

    // Don't sort parameters or psalm will get confused for something like @param Closure():foo|string $param
    $services->set(PhpdocTypesOrderFixer::class)->call('configure', [
        [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
    ]);

    $services->set(NoExtraBlankLinesFixer::class)->call('configure', [
        [
            'tokens' => [
                //                'use', Allow blank lines in import statements to separate functions/classes/constants
                'break',
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'switch',
                'throw',
                'use_trait',
            ],
        ],
    ]);

    // Import base rules
    $configurator->import(SetList::PHP_CS_FIXER_RISKY);

    // Test methods should have an annotation and not be prefixed with "test_"
    $services->set(PhpUnitTestAnnotationFixer::class)->call('configure', [
        [
            'style' => 'annotation',
        ],
    ]);
    // Assertions should be called with $this instead of self::
    $services->set(PhpUnitTestCaseStaticMethodCallsFixer::class)->call('configure', [
        [
            'call_type' => 'this',
        ],
    ]);
    // This breaks assertions that compare object equality but not reference.
    $services->remove(PhpUnitStrictFixer::class);

    // Allow class names inside same class
    $services->remove(SelfAccessorFixer::class);

    $configurator->import(SetList::PSR_12);
    $configurator->import(SetList::SPACES);
    $configurator->import(SetList::ARRAY);
    $configurator->import(SetList::DOCBLOCK);
    $configurator->import(SetList::CLEAN_CODE);
    $configurator->import(SetList::NAMESPACES);
    $configurator->import(SetList::STRICT);
    $configurator->import(SetList::COMMENTS);

    // Only echo, no print.
    $services->set(NoMixedEchoPrintFixer::class);

    $services->set(OrderedImportsFixer::class)->call('configure', [
        [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
    ]);
    $services->set(GlobalNamespaceImportFixer::class)->call('configure', [
        [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
    ]);

    $services->set(GeneralPhpdocAnnotationRemoveFixer::class)->call(
        'configure',
        [
            [
                'annotations' => [
                    //                   'throws', Allow @throws annotation.
                    'author',
                    'package',
                    'group',
                    'covers',
                    'since',
                ],
            ],
        ]
    );
    $services->set(PhpdocNoUselessInheritdocFixer::class);
    $services->set(NoUnusedImportsFixer::class);
};
