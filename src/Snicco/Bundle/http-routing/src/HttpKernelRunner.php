<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting;

use LogicException;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Bundle\HttpRouting\Event\ResponseSent;
use Snicco\Bundle\HttpRouting\Event\TerminatedResponse;
use Snicco\Bundle\HttpRouting\ResponseEmitter\ResponseEmitter;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;

use function add_action;
use function did_action;
use function doing_action;
use function sprintf;

use const PHP_INT_MIN;

final class HttpKernelRunner
{
    private HttpKernel $http_kernel;

    private ServerRequestCreator $request_creator;

    private EventDispatcherInterface $event_dispatcher;

    private ResponseEmitter $emitter;

    private StreamFactoryInterface $stream_factory;

    private ApiRequestDetector $api_request_detector;

    /**
     * @internal
     *
     * @psalm-internal Snicco\Bundle\HttpRouting
     */
    public function __construct(
        HttpKernel $http_kernel,
        ServerRequestCreator $request_creator,
        EventDispatcherInterface $event_dispatcher,
        ResponseEmitter $emitter,
        StreamFactoryInterface $stream_factory,
        ApiRequestDetector $api_request_detector
    ) {
        $this->http_kernel = $http_kernel;
        $this->request_creator = $request_creator;
        $this->event_dispatcher = $event_dispatcher;
        $this->emitter = $emitter;
        $this->stream_factory = $stream_factory;
        $this->api_request_detector = $api_request_detector;
    }

    /**
     * Sets up the runner to send a response an appropriate (later) time based
     * on the request type.
     *
     * @note Unless you are 100% sure what you are doing you should not change the hooks.
     * You have been warned.
     */
    public function listen(
        bool $is_admin_area,
        string $frontend_hook = 'wp_loaded',
        string $api_hook = 'init',
        string $admin_hook = 'admin_init'
    ): void {
        $this->assertNoMagicQuotesAdded();

        $psr_request = $this->request_creator->fromGlobals();

        if ($this->api_request_detector->isAPIRequest($psr_request)) {
            add_action($api_hook, function () use ($psr_request): void {
                $this->dispatchFrontendRequest(Request::fromPsr($psr_request, Request::TYPE_API));
            }, PHP_INT_MIN);
        } elseif ($is_admin_area) {
            add_action($admin_hook, function () use ($psr_request): void {
                $this->dispatchAdminRequest($psr_request);
            }, PHP_INT_MIN);
        } else {
            add_action($frontend_hook, function () use ($psr_request): void {
                $this->dispatchFrontendRequest(Request::fromPsr($psr_request, Request::TYPE_FRONTEND));
            }, PHP_INT_MIN);
        }
    }

    /**
     * Pipes the current request through the application immediately.
     *
     * @note Unless you are 100% what you are doing, should stick to using {@see HttpKernelRunner::listen())
     */
    public function run(): void
    {
        $this->assertNoMagicQuotesAdded();

        $psr_request = $this->request_creator->fromGlobals();

        $type = $this->api_request_detector->isAPIRequest($psr_request)
            ? Request::TYPE_API
            : Request::TYPE_FRONTEND;

        $this->dispatchFrontendRequest(Request::fromPsr($psr_request, $type));
    }

    private function dispatchFrontendRequest(Request $request): void
    {
        $response = $this->http_kernel->handle($request);

        $send_body = true;
        $send_headers = true;

        if ($response instanceof DelegatedResponse) {
            $send_body = false;
            $send_headers = $response->shouldHeadersBeSent();
        }

        if (! $send_headers) {
            return;
        }

        if (! $send_body) {
            $response = $response->withBody($this->stream_factory->createStream(''));
        }

        $this->emitter->emit($response);

        $this->event_dispatcher->dispatch(new ResponseSent($response, $send_body));

        if ($send_body) {
            $this->event_dispatcher->dispatch(new TerminatedResponse());
        }
    }

    private function dispatchAdminRequest(ServerRequestInterface $psr_request): void
    {
        $request = Request::fromPsr($psr_request, Request::TYPE_ADMIN_AREA);

        $response = $this->http_kernel->handle($request);

        $send_body = false;
        $send_body_now = false;

        if ($response instanceof DelegatedResponse) {
            $send_headers = $response->shouldHeadersBeSent();
        } // 200 response codes should be sent (as they are the admin views), but we have to defer them.
        elseif ($response->isSuccessful()) {
            $send_headers = true;
            $send_body = true;
        } else {
            // 300, 400 and 500 status codes are treated as normal responses and are sent entirely immediately.
            $send_headers = true;
            $send_body = true;
            $send_body_now = true;
        }

        if (! $send_headers) {
            return;
        }

        if (! $send_body_now) {
            if ($send_body) {
                $stream = $response->getBody();
                add_action('all_admin_notices', function () use ($stream): void {
                    // Let's hope that the developer did read the docs and only returns an admin view and not
                    // 200MB of string content.
                    echo $stream->__toString();
                });
            }

            $empty_stream = $this->stream_factory->createStream();
            $response = $response->withBody($empty_stream);
            // This is of extreme importance. Not removing this header here will lead to a very sad admin dashboard.
            $response = $response->withoutHeader('content-length');
        }

        // send headers and (empty) body
        $this->emitter->emit($response);

        $this->event_dispatcher->dispatch(new ResponseSent($response, $send_body_now));

        if ($send_body_now) {
            $this->event_dispatcher->dispatch(new TerminatedResponse());
        }
    }

    /**
     * We must create the PSR request before WordPress calls {@see
     * wp_magic_quotes} and nukes the real server request data.
     *
     * {@see wp_magic_quotes} is called in wp-settings.php just after the plugins_loaded hook.
     *
     * @see https://core.trac.wordpress.org/ticket/18322
     */
    private function assertNoMagicQuotesAdded(): void
    {
        if (! did_action('plugins_loaded') || doing_action('plugins_loaded')) {
            return;
        }

        throw new LogicException(
            sprintf(
                "You must call HttpKernelRunner::listen/run before the plugins_loaded hook. Otherwise WordPress will have nuked the SAPI request variables.\nSee: %s",
                'https://core.trac.wordpress.org/ticket/18322'
            )
        );
    }
}
