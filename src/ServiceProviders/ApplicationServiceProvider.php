<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\ExceptionHandling\ShutdownHandler;
    use WPEmerge\Facade\WordpressApi;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;

    class ApplicationServiceProvider extends ServiceProvider {


		public function register() : void {

			$this->container->singleton( WordpressApi::class, function () {

				return new WordpressApi();

			} );

			$this->bindConfig();

			$this->bindShutDownHandler();

		}

		public function bootstrap() : void {


		}


        private function bindShutDownHandler()
        {
            $this->container->singleton(ShutdownHandler::class, function () {

                return new ShutdownHandler($this->requestType());

            });
        }

        private function bindConfig()
        {

            $this->container->instance('request.type', $this->requestType());

        }



    }
