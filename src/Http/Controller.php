<?php


	declare( strict_types = 1 );


	namespace WPMvc\Http;

	use WPMvc\Contracts\ViewFactoryInterface;
    use WPMvc\Routing\UrlGenerator;
    use WPMvc\View\ViewFactory;

    class Controller {


		/**
		 * Middleware.
		 *
		 * @var ControllerMiddleware[]
		 */
		private $middleware = [];

        /**
         * @var ViewFactory
         */
        protected $view_factory;

        /** @var ResponseFactory */
        protected $response_factory;

        /**
         * @var UrlGenerator
         */
        protected $url;

        public function getMiddleware( string $method = null ) : array {

			return collect( $this->middleware )
				->filter( function ( ControllerMiddleware $middleware ) use ( $method ) {

					return $middleware->appliesTo( $method );

				} )
				->map( function ( ControllerMiddleware $middleware ) {

					return $middleware->name();

				} )
				->values()
				->all();


		}

		protected function middleware( string $middleware_name ) : ControllerMiddleware {

			return $this->middleware[] = new ControllerMiddleware( $middleware_name );


		}

		public function giveViewFactory(ViewFactoryInterface $view_factory ) {
		    $this->view_factory = $view_factory;
        }

        public function giveResponseFactory(ResponseFactory $response_factory ) {
		    $this->response_factory = $response_factory;
        }

        public function giveUrlGenerator ( UrlGenerator $url) {
            $this->url = $url;
        }


	}