<?php


	declare( strict_types = 1 );


	namespace BetterWP\Events;



    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use BetterWP\Support\WP;

    class IncomingAdminRequest extends IncomingRequest {

        use IsAction;

    }