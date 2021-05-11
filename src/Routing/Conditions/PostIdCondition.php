<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\UrlableInterface;


	class PostIdCondition implements ConditionInterface, UrlableInterface {

		/**
		 * @var integer
		 */
		private $post_id;

		public function __construct( int $post_id ) {

			$this->post_id = $post_id;
		}

		public function isSatisfied( RequestInterface $request ) : bool {

			return ( is_singular() && $this->post_id === (int) get_the_ID() );
		}

		public function getArguments( RequestInterface $request ) : array {

			return [ 'post_id' => $this->post_id ];
		}

		public function toUrl( $arguments = [] ) :string {

			return get_permalink( $this->post_id );
		}


	}
