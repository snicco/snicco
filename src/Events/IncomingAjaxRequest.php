<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

    use WPEmerge\Http\Request;

    class IncomingAjaxRequest extends IncomingRequest {


		public function __construct(Request $request) {

			parent::__construct($request);

			$this->request->withType(get_class($this));

		}

	}