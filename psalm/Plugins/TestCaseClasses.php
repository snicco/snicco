<?php

declare(strict_types=1);


use Codeception\TestCase\WPTestCase;
use PHPUnit\Framework\TestCase;
use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
use Snicco\Bridge\Blade\Tests\BladeTestCase;
use Snicco\Bundle\Testing\Functional\WebTestCase;
use Snicco\Component\BetterWPDB\Tests\BetterWPDBTestCase;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

final class TestCaseClasses implements AfterClassLikeVisitInterface
{

    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event)
    {
        $storage = $event->getStorage();

        if (!$storage->user_defined) {
            return;
        }

        $parents = $storage->parent_classes;

        if (empty($parents)) {
            return;
        }

        $suppress_for = [
            TestCase::class,
            MiddlewareTestCase::class,
            Codeception\Test\Unit::class,
            HttpRunnerTestCase::class,
            WPTestCase::class,
            BladeTestCase::class,
            BetterWPDBTestCase::class,
            WebTestCase::class,
        ];

        if (count(array_intersect($parents, $suppress_for))) {
            $storage->suppressed_issues[] = 'UnusedClass';
            $storage->suppressed_issues[] = 'PropertyNotSetInConstructor';
        }
    }

}

