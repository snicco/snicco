<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Exception;

use LogicException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;

final class RequestHasNoType extends LogicException
{

    /**
     * @param mixed $type
     */
    public static function becauseTheTypeIsNotAnInteger($type): RequestHasNoType
    {
        return self::createNew(gettype($type));
    }

    private static function createNew(string $received_value): RequestHasNoType
    {
        return new self(
            sprintf(
                "The request's type attribute has to be one of [%s].\nGot [%s].",
                implode(',', [Request::TYPE_FRONTEND, Request::TYPE_ADMIN_AREA, Request::TYPE_API]),
                $received_value
            )
        );
    }

    public static function becauseTheRangeIsNotCorrect(int $type): RequestHasNoType
    {
        return self::createNew((string)$type);
    }

}