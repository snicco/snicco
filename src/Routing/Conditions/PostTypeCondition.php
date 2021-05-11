<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;


	class PostTypeCondition implements ConditionInterface {


		private $post_type;


		public function __construct( string $post_type ) {

			$this->post_type = $post_type;
		}


		public function isSatisfied( RequestInterface $request ) : bool {

			return ( is_singular() && $this->post_type === get_post_type() );
		}


		public function getArguments( RequestInterface $request ) : array {

			return [ 'post_type' => $this->post_type ];
		}

	}
