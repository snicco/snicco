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

                $response->withHeader($name, $value);

            }

            return $response;

        }

        public function make(int $status_code, $reason_phrase = '') : Response
        {

            $psr_response = $this->response_factory->createResponse($status_code, $reason_phrase);

            return new Response($psr_response);

        }

        public function html(string $html) : Response
        {

            return $this->make(200)
                        ->html($this->stream_factory->createStream($html));

        }

        public function json($content) : Response
        {

            /** @todo This needs more parsing or a dedicated JsonResponseClass */
            return $this->make(200)
                        ->json(
                            $this->stream(json_encode($content, ))
                        );

        }

        public function null() : Response
        {

            return new NullResponse($this->response_factory->createResponse(204));

        }

        private function stream(string $content) : StreamInterface
        {

            return $this->stream_factory->createStream($content);

        }

        public function prepareResponse( $response ) : Response {

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

                return $this->prepareResponse(
                    $response->toResponsable()
                );

            }

            return $this->null();

        }

    }