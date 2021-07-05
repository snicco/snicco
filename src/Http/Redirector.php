<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;
    use WPEmerge\Contracts\AbstractRedirector;
    use WPEmerge\Support\WP;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Routing\UrlGenerator;

    class Redirector extends AbstractRedirector
    {

        public function createRedirectResponse ( string $path, int $status_code = 302 ) : RedirectResponse {

            $this->validateStatusCode($status_code);

            $psr_response = $this->response_factory->createResponse($status_code);

            $response = new RedirectResponse($psr_response);

            return $response->to($path);

        }


    }