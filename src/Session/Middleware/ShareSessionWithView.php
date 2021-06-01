<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Middleware;

    use Illuminate\Support\ViewErrorBag;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\SessionStore;
    use WPEmerge\View\GlobalContext;

    class ShareSessionWithView extends Middleware
    {

        /**
         * @var GlobalContext
         */
        private $global_context;
        /**
         * @var SessionStore
         */
        private $session;

        public function __construct(GlobalContext $global_context, SessionStore $session)
        {
            $this->global_context = $global_context;
            $this->session = $session;
        }

        public function handle(Request $request, Delegate $next)
        {
            // If the current session has an "errors" variable bound to it, we will share
            // its value with all view instances so the views can easily access errors
            // without having to bind. An empty bag is set when there aren't errors.
            $errors = $request->getSession()->get('errors') ?: new ViewErrorBag();

            $this->global_context->add('errors', $errors);

            $this->global_context->add('session', $this->session);

            return $next($request);

        }

    }