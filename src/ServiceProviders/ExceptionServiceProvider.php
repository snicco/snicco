<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

    use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\ExceptionHandling\NullErrorHandler;
	use WPEmerge\ExceptionHandling\ProductionErrorHandler;
	use WPEmerge\Factories\ErrorHandlerFactory;
    use WPEmerge\Http\Request;

    class ExceptionServiceProvider extends ServiceProvider {

		public function register() : void {

            $this->bindConfig();

            $this->bindErrorHandlerInterface();

		}

		public function bootstrap() : void {

			$error_handler = $this->container->make( ErrorHandlerInterface::class );

			$error_handler->register();

			$this->container->instance( ErrorHandlerInterface::class, $error_handler );

		}

        private function bindConfig() : void
        {

            $this->config->extend('exception_handling.global', false);

            // We bind the class name only
            $this->container->instance(ProductionErrorHandler::class, ProductionErrorHandler::class);
        }

        private function bindErrorHandlerInterface() : void
        {

            $this->container->singleton(ErrorHandlerInterface::class, function () {

                if ( ! $this->config->get('exception_handling.enable', false)) {

                    return new NullErrorHandler();

                }

                /** @var Request $request */
                $request = $this->container->make(Request::class);

                return ErrorHandlerFactory::make(
                    $this->container,
                    $this->config->get('exception_handling.debug', false),
                    $request->isAjax(),
                    $this->config->get('exception_handling.editor', 'phpstorm')

                );
            });
        }

    }
