<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Controllers\Web;

	use Tests\stubs\Foo;
	use Tests\stubs\Bar;
	use Tests\stubs\TestRequest;
    use WPEmerge\Http\Request;
    use WPEmerge\Http\Response;

	class TeamsController {

		public function handle( Request $request, string $team, string $player )  {

			return $team . ':' . $player;


		}

		public function withDependencies( Request $request, string $team, string $player, Foo $foo, Bar $bar)  {

			return $team . ':' . $player . ':' . $foo->foo . ':' . $bar->bar;


		}

		public function withConditions( Request $request, $team, $player, $baz, $biz ,Foo $foo, Bar $bar)  {

			return $team . ':' . $player . ':' .  $baz . ':' . $biz  . ':' . $foo->foo . ':' . $bar->bar;


		}

		public function _withConditions( Request $request, string $team, string $player, string $baz, string $biz ,Foo $foo, Bar $bar)  {

			return $team . ':' . $player . ':' .  $baz . ':' . $biz  . ':' . $foo->foo . ':' . $bar->bar;


		}


	}