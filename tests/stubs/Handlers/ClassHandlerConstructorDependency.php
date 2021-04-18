<?php


	namespace Tests\stubs\Handlers;

	use Tests\stubs\TestResponse;
	use WPEmerge\Requests\Request;

	class ClassHandlerConstructorDependency {


		/**
		 */
		private $dependency;

		public function __construct( Dependency $foo ) {

			$this->dependency = $foo;
		}

		public function handle( Request $request )  {

			$request->body = $this->dependency->foo . $request->bar;

			return new TestResponse($request );

		}

		public function teams( Request $request, string $team )  {

			$request->body  = $team;

			return new TestResponse($request );

		}

		public function teamDependency ( Request $request, FavoriteTeam $team )  {

			$request->body  = $team->name;

			return new TestResponse($request );


		}

	}

	class Dependency {

		public $foo  = 'foo';

	}

	class FavoriteTeam {

		public $name = 'dortmund';

	}

