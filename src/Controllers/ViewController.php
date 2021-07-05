<?php


	declare( strict_types = 1 );


	namespace WPMvc\Controllers;


    use WPMvc\Http\Controller;
    use WPMvc\Http\Psr7\Response;

    class ViewController extends Controller {


		public function handle( ...$args ) : Response {

			[$view, $data, $status, $headers] = array_slice($args, -4);

			return $this->response_factory->view( $view, $data, $status, $headers );

		}

	}