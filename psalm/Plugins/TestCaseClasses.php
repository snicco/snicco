<?php

declare(strict_types=1);


use Codeception\TestCase\WPTestCase;
use PHPUnit\Framework\TestCase;
use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
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

        if (in_array(TestCase::class, $parents, true)
            || in_array(MiddlewareTestCase::class, $parents, true)
            || in_array(Codeception\Test\Unit::class, $parents, true)
            || in_array(HttpRunnerTestCase::class, $parents, true)
            || in_array(WPTestCase::class, $parents, true)) {
            $storage->suppressed_issues[] = 'UnusedClass';
            $storage->suppressed_issues[] = 'PropertyNotSetInConstructor';
        }
    }
}