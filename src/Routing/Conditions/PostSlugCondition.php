<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;

	/**
	 * Check against the current post's slug.
	 *
	 * @codeCoverageIgnore
	 */
	class PostSlugCondition implements ConditionInterface {

		/**
		 * Post slug to check against
		 *
		 * @var string
		 */
		protected $post_slug = '';

		/**
		 * Constructor
		 *
		 * @codeCoverageIgnore
		 *
		 * @param  string  $post_slug
		 */
		public function __construct( $post_slug ) {

			$this->post_slug = $post_slug;
		}

		/**
		 * {@inheritDoc}
		 */
		public function isSatisfied( RequestInterface $request ) {

			$post = get_post();

			return ( is_singular() && $post && $this->post_slug === $post->post_name );
		}

		/**
		 * {@inheritDoc}
		 */
		public function getArguments( RequestInterface $request ) {

			return [ 'post_slug' => $this->post_slug ];
		}

	}
