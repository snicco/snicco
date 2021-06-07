<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;

    class IncomingGlobalRequest extends IncomingRequest
    {

        use DispatchesConditionally;

        public function shouldDispatch() : bool
        {

            return ! $this->isNativeWordpressJsonApiRequest();

        }



    }