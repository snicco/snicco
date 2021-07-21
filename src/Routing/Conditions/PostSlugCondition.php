<?php


	declare( strict_types = 1 );


	namespace Snicco\Routing\Conditions;

	use Snicco\Contracts\ConditionInterface;
    use Snicco\Http\Psr7\Request;

    class PostSlugCondition implements ConditionInterface {

		/**
		 * Post slug to check against
		 *
		 * @var string
		 */
		private $post_slug;

		public function __construct( string $post_slug ) {

			$this->post_slug = $post_slug;
		}

		public function isSatisfied( Request $request ) :bool {

			$post = get_post();

			return ( is_singular() && $post && $this->post_slug === $post->post_name );
		}

		public function getArguments( Request $request ) :array  {

			return [ 'post_slug' => $this->post_slug ];
		}

	}
