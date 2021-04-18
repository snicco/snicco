<?php


	namespace Tests\stubs;

	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\ServiceProviderInterface;

	class TestProvider2 implements ServiceProviderInterface {


		public function register( ContainerAdapter $container ) {
			// TODO: Implement register() method.
		}

		public function bootstrap( ContainerAdapter $container ) {
			// TODO: Implement bootstrap() method.
		}

	}