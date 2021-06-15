<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;



    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWpHooks\Traits\IsAction;
    use WPEmerge\Facade\WP;

    class IncomingAdminRequest extends IncomingRequest {


        use IsAction;



    }