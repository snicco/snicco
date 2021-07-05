<?php


	declare( strict_types = 1 );


	namespace WPMvc\Routing\Conditions;

	use WPMvc\Contracts\ConditionInterface;
    use WPMvc\Http\Psr7\Request;

    class PostStatusCondition implements ConditionInterface {

		/**
		 * @var string
		 */
		private $post_status;


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
