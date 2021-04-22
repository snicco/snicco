<?php


	namespace WPEmerge\Events;

	class IncomingAjaxRequest extends IncomingRequest {


		public function __construct() {

			parent::__construct();

			$this->request->setType(get_class($this));

		}

	}