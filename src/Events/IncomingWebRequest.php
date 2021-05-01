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

			$this->request->setType( get_class( $this ) );

		}

		public function shouldDispatch() : bool {

			return true;

			return ! is_admin() && ! str_contains( $this->request->getFullUrlString(), admin_url() );

		}


		/** @todo Replace this with some sort of fallback route logic. */
		public function default() {

			if ( ! $this->request->route() && ! $this->request->force_route_match ) {

				return $this->template;

			}

		}


	}