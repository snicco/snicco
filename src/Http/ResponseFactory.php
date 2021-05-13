<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Http;

	use Http\Message\ResponseFactory as Psr7ResponseFactory;
	use Psr\Http\Message\ResponseInterface as Prs7Response;
    use WPEmerge\Contracts\ResponsableInterface;
    use WPEmerge\Contracts\ViewServiceInterface as ViewService;

	class ResponseFactory  {

		/**
		 * @var ViewService
		 */
		private $view;
        /**
         * @var Psr7ResponseFactory
         */
        private $factory;

        public function __construct( ViewService $view, Psr7ResponseFactory $factory) {

			$this->view = $view;
            $this->factory = $factory;

        }

		public function view( string $view, array $data = [], $status = 200, array $headers = [] ) : Prs7Response {

			$content = $this->view->make($view)->with($data)->toString();

			$psr_response = $this->make($status, $content, $headers)
                             ->withHeader('Content-Type', 'text/html');

			return new Response($psr_response);


		}

		public function make(int $status_code, $body, $headers = [] ) : Response
        {

            $psr_response = $this->factory->createResponse($status_code, null , $headers , $body, );

            return new Response($psr_response);

        }

		public function html (string $html) : Response
        {

            return $this->make(200, $html)->html();

        }

        public function json ( $content ) : Response
        {
            /** @todo This needs more parsing or a dedicated JsonResponseClass */
            return $this->make(200, json_encode($content))->json();

        }

        public function toResponse ( ResponsableInterface $responsable ) : Response
        {

            return new Response($responsable->toResponse());

        }

        public function null() :Response
        {

            return new NullResponse($this->factory->createResponse(204));

        }

    }