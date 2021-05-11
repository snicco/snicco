<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;


	class PostTemplateCondition implements ConditionInterface {

		/**
		 * @var string
		 */
		private $post_template;

		/**
		 * @var string[]
		 */
		private $post_types;


		public function __construct( string $post_template, $post_types = [] ) {

			$this->post_template = $post_template;
			$this->post_types    = is_array( $post_types ) ? $post_types : [ $post_types ];
		}


		public function isSatisfied( RequestInterface $request ) : bool {

			$template = get_post_meta( (int) get_the_ID(), '_wp_page_template', true );
			$template = $template ? : 'default';

			return ( is_singular( $this->post_types ) && $this->post_template === $template );
		}


		public function getArguments( RequestInterface $request ) : array {

			return [ 'post_template' => $this->post_template, 'post_types' => $this->post_types ];
		}

	}
