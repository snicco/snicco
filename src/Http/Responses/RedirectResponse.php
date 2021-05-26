<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Http\Responses;


	use WPEmerge\Http\Psr7\Response;

    class RedirectResponse extends Response {


		public function to( string $url ) : RedirectResponse {

		    return $this->new($this->withHeader('Location', $url));

		}


	}
