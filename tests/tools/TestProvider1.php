<?php


	namespace WPEmergeTestTools;

	use Contracts\ContainerAdapter;
	use WPEmerge\ServiceProviders\ServiceProviderInterface;

	class TestProvider1 implements ServiceProviderInterface {


		public function register( ContainerAdapter $container ) {

			$container['foo'] = 'bar';

		}

		public function bootstrap( ContainerAdapter $container ) {

			$container['bar'] = 'baz';

		}

	}