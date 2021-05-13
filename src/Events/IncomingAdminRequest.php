<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;



	use BetterWpHooks\Traits\DispatchesConditionally;
    use WPEmerge\Http\Request;

    class IncomingAdminRequest extends IncomingRequest {

		use DispatchesConditionally;

		public function __construct(Request $request) {

			parent::__construct($request);

			$this->request->setType(get_class($this));

		}

		public function shouldDispatch() : bool {

			return is_admin();

		}

	}