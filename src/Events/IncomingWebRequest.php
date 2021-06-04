<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use BetterWpHooks\Traits\DispatchesConditionally;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Support\Str;

    class IncomingWebRequest extends IncomingRequest {

		use DispatchesConditionally;

		/**
		 * @var string
		 */
		public $template;


		public function __construct( string $template = '', Request $request = null ) {

			$this->template = $template;

			parent::__construct($request);

		}

		public function shouldDispatch() : bool {

			return ! is_admin() && ! Str::contains( $this->request->getUrl(), admin_url() );

		}

		public function default() : ?string {

			if ( ! $this->has_matching_route && ! $this->force_route_match ) {

				return $this->template;

			}

			return null;

		}


	}