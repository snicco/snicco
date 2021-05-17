<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade;

    use PHPUnit\Framework\Assert;
    use WPEmerge\Contracts\ViewInterface;

    trait AssertBladeView
    {

        public function assertViewContent(string $expected,  $actual) {

            $actual = ($actual instanceof ViewInterface) ? $actual->toString() :$actual;

            Assert::assertSame($expected, trim($actual));

        }

    }