<?php


	declare( strict_types = 1 );


	namespace BetterWP\Events;

    use BetterWpHooks\Traits\DispatchesConditionally;
    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Listeners\ShortCircuit404;
    use BetterWP\Support\Str;

    class IncomingWebRequest extends IncomingRequest {

		use DispatchesConditionally;

		/**
		 * @var string
		 */
		public $template_wordpress_tried_to_load;

		public function __construct( Request $request, string $template ) {

			$this->template_wordpress_tried_to_load = $template;

			parent::__construct($request);

		}

		public function shouldDispatch() : bool {

			return ! is_admin()
                && ! Str::contains( $this->request->url(), admin_url() );

		}

		public function default() : ?string {

            return $this->determineTemplateToLoad();

        }

        private function determineTemplateToLoad() : ?string
        {

            if ( ! $this->has_matching_route ) {

                if ( $this->is404() ) {

                    return get_404_template();

                }

                return $this->template_wordpress_tried_to_load;

            }

            return null;
        }

        private function is404() : bool
        {

            global $wp, $wp_query;

            if ( ! $wp_query instanceof \WP_Query || ! $wp instanceof \WP ) {

                return false;

            }

            if ( $wp_query->is_404() ) {

                return true;

            }

            ApplicationEvent::forgetOne(Wp404::class, [ShortCircuit404::class, '__invoke']);

            $wp->handle_404();

            return $wp_query->is_404();

        }


    }