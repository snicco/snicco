<?php


	namespace Tests\stubs\Handlers;

	use WPEmerge\Requests\Request;

	class ClassHandlerConstructorDependency {


		/**
		 */
		private $dependency;

		public function __construct( Dependency $foo ) {

			$this->dependency = $foo;
		}

		public function handle( Request $request, string $view ) : string {

			return $this->dependency->foo . $request->bar . $view;

		}

		public function teams( Request $request, string $view, $team ) : string {

			return $team;

		}

		public function teamDependency ( Request $request, string $view, FavoriteTeam $team ) : string {

			return $team->name;

		}

	}

	class Dependency {

		public $foo  = 'foo';

	}

	class FavoriteTeam {

		public $name = 'dortmund';

	}

