<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use WPEmerge\Contracts\ServiceProvider;

	class TestProvider extends ServiceProvider {


		public function register() : void {

			$this->container['foo'] = 'bar';

		}

		public function bootstrap() : void {

			$this->container['bar'] = 'baz';

		}

	}