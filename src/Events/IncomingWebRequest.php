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
		 * @var \WPEmerge\Http\Request
		 */
		public $request;


		public function __construct( string $template ) {

			$this->template = $template;

			parent::__construct();

			$this->request->setType( get_class( $this ) );

		}

		public function shouldDispatch() : bool {

			return ! is_admin() && ! str_contains( $this->request->url(), admin_url() );

		}


		public function default() : ?string {

			if ( ! $this->request->route() && ! $this->force_route_match ) {

				return $this->template;

			}

			return null;

		}


	}