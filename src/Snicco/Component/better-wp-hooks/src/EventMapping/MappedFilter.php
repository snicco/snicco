<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\EventMapping;

/**
 * Use this interface if you want to map your event to a WordPress filter.
 */
interface MappedFilter extends MappedHook
{
    /**
     * The returned value of this method will be returned to the calling filter.
     * One way to use this is to return the value of a PUBLIC and TYPE-HINTED
     * property on your event object. That way all your listeners can interact
     * with the passed event and manipulate the property while still always
     * ensuring that you never return a type different from what the firing
     * WordPress filter expects.
     *
     * @return mixed
     */
    public function filterableAttribute();
}
