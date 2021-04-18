<?php


	namespace Tests\stubs;

	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\ServiceProviderInterface;

	class TestProvider1 implements ServiceProviderInterface {


		public function register( ContainerAdapter $container ) {

			$container['foo'] = 'bar';

		}

		public function bootstrap( ContainerAdapter $container ) {

			$container['bar'] = 'baz';

		}

	}