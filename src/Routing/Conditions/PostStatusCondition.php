<?php


	declare( strict_types = 1 );


	namespace Snicco\Routing\Conditions;

	use Snicco\Contracts\ConditionInterface;
    use Snicco\Http\Psr7\Request;

    class PostStatusCondition implements ConditionInterface {

		private string $post_status;

		public function __construct( string $post_status ) {

			$this->post_status = $post_status;
		}


		public function isSatisfied( Request $request ) :bool {

			$post = get_post();

			return ( is_singular() && $post && $this->post_status === $post->post_status );
		}


		public function getArguments( Request $request ) : array {

			return [ 'post_status' => $this->post_status ];
		}

	}
