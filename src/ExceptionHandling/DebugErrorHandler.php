<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ExceptionHandling;

	use Throwable;
    use Whoops\Handler\JsonResponseHandler;
    use Whoops\RunInterface;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Traits\HandlesExceptions;

	class DebugErrorHandler implements ErrorHandlerInterface {

		use HandlesExceptions;

		/** @var RunInterface */
		private $whoops;

		public function __construct( RunInterface $whoops) {

			$this->whoops = $whoops;

		}

		public function handleException( $exception, ?Request $request = null )  {

		    $request = $request ?? $this->resolveRequestFromContainer();

		    if ( $request && $request->isExpectingJson() ) {

                $json_handler = new JsonResponseHandler();
                $json_handler->addTraceToOutput( true );
                $this->whoops->prependHandler( $json_handler );

            }

			$method = RunInterface::EXCEPTION_HANDLER;

			$this->whoops->{ $method }( $exception );

			UnrecoverableExceptionHandled::dispatch();


		}

		public function transformToResponse( Throwable $exception, Request $request) : ?Response {

			 $this->handleException( $exception, $request );

			 return null;

		}

        public function unrecoverable(Throwable $exception)
        {
            $this->handleException($exception);
        }

    }