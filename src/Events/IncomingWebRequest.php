<?php


	declare( strict_types = 1 );


	namespace Snicco\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use Snicco\Support\Str;

    class IncomingWebRequest extends IncomingRequest
    {

        use IsAction;
        use DispatchesConditionally;

        public function shouldDispatch() : bool
        {

            return $this->request->isWpFrontEnd() && ! Str::contains($this->request->path(), '/wp-json');
        }

    }