<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;


    use BetterWpHooks\Traits\DispatchesConditionally;
    use WPEmerge\Support\Arr;

    class IncomingAjaxRequest extends IncomingRequest {

        use DispatchesConditionally;

        public function shouldDispatch() : bool
        {

            if ( $this->request->isReadVerb() ) {

                return Arr::has($this->request->getQueryParams(), 'action');

            }

            return Arr::has($this->request->getParsedBody(), 'action');

        }

    }