<?php


	namespace Tests\stubs\Controllers\Web;

	use Tests\stubs\Models\Team;
	use Tests\stubs\TestResponse;
	use WPEmerge\Requests\Request;

	class TeamsController {

		public function handle( Request $request, Team $team ) {

			$request->body = $team->name;

			return new TestResponse( $request );

		}


		public function noTypeHint( Request $request, $team ) {

			$request->body = $team;

			return new TestResponse( $request );

		}

		public function never( Request $request, Team $team ) {

			$GLOBALS['TeamsControllerExecuted'] = true;

			$request->body = $team->name;

			return new TestResponse( $request );

		}

	}