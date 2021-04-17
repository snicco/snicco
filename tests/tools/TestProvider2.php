<?php


	namespace WPEmergeTestTools;

	use Contracts\ContainerAdapter;
	use WPEmerge\ServiceProviders\ServiceProviderInterface;

	class TestProvider2 implements ServiceProviderInterface {


		public function register( ContainerAdapter $container ) {
			// TODO: Implement register() method.
		}

		public function bootstrap( ContainerAdapter $container ) {
			// TODO: Implement bootstrap() method.
		}

	}