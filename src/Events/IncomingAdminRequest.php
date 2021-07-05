<?php


	declare( strict_types = 1 );


	namespace WPMvc\Events;



    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use WPMvc\Support\WP;

    class IncomingAdminRequest extends IncomingRequest {

        use IsAction;

    }