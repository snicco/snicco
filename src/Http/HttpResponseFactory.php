<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\StreamFactoryInterface as Psr17StreamFactory;
    use Psr\Http\Message\StreamInterface;
    use WPEmerge\Contracts\ResponsableInterface;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Contracts\ViewServiceInterface as ViewService;
    use WPEmerge\Support\Arr;

    class HttpResponseFactory implements ResponseFactory
    {

        /**
         * @var ViewService
         */
        private $view;
        /**
         * @var Psr17ResponseFactory
         */
        private $response_factory;

        /**
         * @var Psr17StreamFactory
         */
        private $stream_factory;

        public function __construct(ViewService $view, Psr17ResponseFactory $response, Psr17StreamFactory $stream)
        {

            $this->view = $view;
            $this->response_factory = $response;
            $this->stream_factory = $stream;

        }

        public function view(string $view, array $data = [], $status = 200, array $headers = []) : Response
        {

            $content = $this->view->make($view)->with($data)->toString();

            $psr_response = $this->make($status)
                                 ->html($this->stream_factory->createStream($content));

            $response = new Response($psr_response);

            foreach ($headers as $name => $value) {

               $response = $response->withHeader($name, $value);

            }

            return $response;

        }

        public function make(int $status_code, $reason_phrase = '') : Response
        {

            $psr_response = $this->response_factory->createResponse($status_code, $reason_phrase);

            return new Response($psr_response);

        }

        public function html(string $html, int $status_code = 200 ) : Response
        {

            return $this->make($status_code)
                        ->html($this->stream_factory->createStream($html));

        }

        public function json($content, int $status = 200 )  : Response
        {


            /** @todo This needs more parsing or a dedicated JsonResponseClass */
            return $this->make($status)
                        ->json(
                            $this->stream(json_encode($content))
                        );

        }

        public function null() : NullResponse
        {

            return new NullResponse($this->response_factory->createResponse(204));

        }

        private function stream(string $content) : StreamInterface
        {

            return $this->stream_factory->createStream($content);

        }

        public function toResponse( $response ) : Response {

            if ( $response instanceof Response ) {

                return $response;

            }

            if ( $response instanceof ResponseInterface ) {

                return new Response($response);

            }

            if ( is_string( $response ) ) {

                return $this->html($response);

            }

            if ( is_array( $response ) ) {

                return $this->json($response);

            }

            if ( $response instanceof ResponsableInterface ) {

                return $this->toResponse(
                    $response->toResponsable()
                );

            }

            return $this->invalidResponse();

        }

        public function redirect( int $status_code = 302 ) : RedirectResponse
        {
            return new RedirectResponse( $this->make( $status_code) );
        }

        public function invalidResponse() : InvalidResponse
        {
            return new InvalidResponse($this->response_factory->createResponse(500));
        }

        public function createResponse(int $code = 200, string $reasonPhrase = '') : ResponseInterface
        {
            return $this->response_factory->createResponse($code, $reasonPhrase);
        }

    }