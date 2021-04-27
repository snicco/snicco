<?php


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\UrlableInterface;

	/**
	 * Check against the current post's id.
	 *
	 */
	class PostIdCondition implements ConditionInterface, UrlableInterface {

		/**
		 * Post id to check against
		 *
		 * @var integer
		 */
		protected $post_id = 0;

		/**
		 * Constructor
		 *
		 *
		 * @param  integer  $post_id
		 */
		public function __construct( $post_id ) {

			$this->post_id = (int) $post_id;
		}

		/**
		 */
		public function isSatisfied( RequestInterface $request ) {

			return ( is_singular() && $this->post_id === (int) get_the_ID() );
		}

		/**
		 */
		public function getArguments( RequestInterface $request ) {

			return [ 'post_id' => $this->post_id ];
		}

		/**
		 */
		public function toUrl( $arguments = [] ) {

			return get_permalink( $this->post_id );
		}

	}
