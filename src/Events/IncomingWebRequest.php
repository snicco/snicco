<?php


	namespace WPEmerge\Events;

	use BetterWpHooks\Traits\DispatchesConditionally;

	class IncomingWebRequest extends IncomingRequest {

		use DispatchesConditionally;


		/**
		 * @var string
		 */
		public $template;

		/**
		 * @var \WPEmerge\Requests\Request
		 */
		public $request;

		public function __construct( string $template ) {

			$this->template = $template;

			parent::__construct();

			$this->request->setType(get_class($this));

		}

		public function shouldDispatch() : bool {

			return ! is_admin();

		}

		public function default() : ?string {


			if ( ! $this->request->route() ) {

				return $this->template;

			}


		}

		public function payload () {

			return  $this->request;

		}

	}