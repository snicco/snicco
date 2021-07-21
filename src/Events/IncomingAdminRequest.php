<?php


	declare( strict_types = 1 );


	namespace Snicco\Events;



    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use Snicco\Support\WP;

    class IncomingAdminRequest extends IncomingRequest {

        use IsAction;

    }