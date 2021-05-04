<?php


	namespace Tests\stubs\Controllers\Web;

	use Tests\stubs\Foo;
	use Tests\stubs\Bar;
	use Tests\stubs\TestResponse;
	use Tests\TestRequest;

	class TeamsController {

		public function handle( TestRequest $request, string $team, string $player ) : TestResponse {

			$request->body = $team . ':' . $player;

			return new TestResponse( $request );

		}

		public function withDependencies( TestRequest $request, string $team, string $player, Foo $foo, Bar $bar) : TestResponse {

			$request->body = $team . ':' . $player . ':' . $foo->foo . ':' . $bar->bar;

			return new TestResponse( $request );

		}

		public function withConditions( TestRequest $request, $team, $player, $baz, $biz ,Foo $foo, Bar $bar) : TestResponse {

			$request->body = $team . ':' . $player . ':' .  $baz . ':' . $biz  . ':' . $foo->foo . ':' . $bar->bar;

			return new TestResponse( $request );

		}


	}