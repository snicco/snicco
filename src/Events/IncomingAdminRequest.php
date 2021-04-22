<?php


	namespace WPEmerge\Events;



	class IncomingAdminRequest extends IncomingRequest {



		public function __construct() {

			parent::__construct();

			$this->request->setType(get_class($this));

		}

		public function payload( ) {

			return $this->request;

		}

	}