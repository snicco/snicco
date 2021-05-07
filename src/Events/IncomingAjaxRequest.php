<?php


	namespace WPEmerge\Events;

	use WPEmerge\Contracts\RequestInterface;

	class IncomingAjaxRequest extends IncomingRequest {


		public function __construct(RequestInterface $request) {

			parent::__construct($request);

			$this->request->setType(get_class($this));

		}

	}