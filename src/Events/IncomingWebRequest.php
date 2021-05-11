<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use BetterWpHooks\Traits\DispatchesConditionally;
	use WPEmerge\Contracts\RequestInterface;

	class IncomingWebRequest extends IncomingRequest {

		use DispatchesConditionally;

		/**
		 * @var string
		 */
		public $template;


		public function __construct( string $template, RequestInterface $request ) {

			$this->template = $template;

			parent::__construct($request);

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