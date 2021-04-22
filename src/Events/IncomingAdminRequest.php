<?php


	namespace WPEmerge\Events;



	use BetterWpHooks\Traits\DispatchesConditionally;

	class IncomingAdminRequest extends IncomingRequest {

		use DispatchesConditionally;

		public function __construct() {

			parent::__construct();


		}

		public function shouldDispatch() : bool {

			return is_admin();

		}

	}