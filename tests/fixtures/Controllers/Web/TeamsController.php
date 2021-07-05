<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Controllers\Web;

	use Tests\fixtures\TestDependencies\Foo;
	use Tests\fixtures\TestDependencies\Bar;
    use BetterWP\Http\Psr7\Request;

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



	}