<?php


    declare(strict_types = 1);


    namespace BetterWP\Http;

    use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;
    use BetterWP\Contracts\AbstractRedirector;
    use BetterWP\Support\WP;
    use BetterWP\Http\Psr7\Response;
    use BetterWP\Http\Responses\RedirectResponse;
    use BetterWP\Routing\UrlGenerator;

    class Redirector extends AbstractRedirector
    {

        public function createRedirectResponse ( string $path, int $status_code = 302 ) : RedirectResponse {

            $this->validateStatusCode($status_code);

            $psr_response = $this->response_factory->createResponse($status_code);

            $response = new RedirectResponse($psr_response);

            return $response->to($path);

        }


    }