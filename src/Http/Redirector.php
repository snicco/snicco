<?php


    declare(strict_types = 1);


    namespace WPMvc\Http;

    use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;
    use WPMvc\Contracts\AbstractRedirector;
    use WPMvc\Support\WP;
    use WPMvc\Http\Psr7\Response;
    use WPMvc\Http\Responses\RedirectResponse;
    use WPMvc\Routing\UrlGenerator;

    class Redirector extends AbstractRedirector
    {

        public function createRedirectResponse ( string $path, int $status_code = 302 ) : RedirectResponse {

            $this->validateStatusCode($status_code);

            $psr_response = $this->response_factory->createResponse($status_code);

            $response = new RedirectResponse($psr_response);

            return $response->to($path);

        }


    }