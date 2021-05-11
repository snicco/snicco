<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Controllers\Web;

	use Tests\stubs\Foo;
	use Tests\stubs\Bar;
	use Tests\TestRequest;
	use WPEmerge\Http\Response;

	class TeamsController {

		public function handle( TestRequest $request, string $team, string $player ) : Response {

			$request->body = $team . ':' . $player;

			return new Response($request->body);

		}

		public function withDependencies( TestRequest $request, string $team, string $player, Foo $foo, Bar $bar) : Response {

			$request->body = $team . ':' . $player . ':' . $foo->foo . ':' . $bar->bar;

			return new Response($request->body);

		}

		public function withConditions( TestRequest $request, $team, $player, $baz, $biz ,Foo $foo, Bar $bar) : Response {

			$request->body = $team . ':' . $player . ':' .  $baz . ':' . $biz  . ':' . $foo->foo . ':' . $bar->bar;

			return new Response($request->body);

		}


	}