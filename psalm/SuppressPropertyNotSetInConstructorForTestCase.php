<?php

declare(strict_types=1);


use PHPUnit\Framework\TestCase;
use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;

final class SuppressPropertyNotSetInConstructorForTestCase implements AfterClassLikeVisitInterface
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
            || in_array(MiddlewareTestCase::class, $parents, true)) {
            $storage->suppressed_issues[-1] = 'PropertyNotSetInConstructor';
        }
    }
}