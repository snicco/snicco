<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Middleware;

    use Illuminate\Support\ViewErrorBag;
    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\Session;
    use WPEmerge\View\GlobalContext;

    class ShareSessionWithView extends Middleware
    {

        /**
         * @var GlobalContext
         */
        private $global_context;


        public function __construct(GlobalContext $global_context)
        {
            $this->global_context = $global_context;
        }

        public function handle(Request $request, Delegate $next) : ResponseInterface
        {

            $session = $request->session();

            // If the current session has an "errors" variable bound to it, we will share
            // its value with all view instances so the views can easily access errors
            // without having to bind. An empty bag is set when there aren't errors.
            $errors = $session->errors();

            $this->global_context->add('errors', $errors);

            $this->global_context->add('session', $session);

            return $next($request);

        }

    }