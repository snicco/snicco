<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;



	use BetterWpHooks\Traits\DispatchesConditionally;
	use WPEmerge\Contracts\RequestInterface;

	class IncomingAdminRequest extends IncomingRequest {

		use DispatchesConditionally;

		public function __construct(RequestInterface $request) {

			parent::__construct($request);

			$this->request->setType(get_class($this));

		}

		public function shouldDispatch() : bool {

			return is_admin();

		}

	}