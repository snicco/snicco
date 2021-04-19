<?php


	namespace Tests\stubs\Controllers\Web;

	use Tests\stubs\Team;
	use Tests\stubs\TestResponse;
	use WPEmerge\Requests\Request;

	class TeamsController {

		public function handle( Request $request,  Team $team ) {

			$request->body = $team->name;

			return new TestResponse($request);

		}

		public function never( Request $request, Team $team ) {

			$GLOBALS['TeamsControllerExecuted'] = TRUE;

			$request->body = $team->name;

			return new TestResponse($request);

		}

	}