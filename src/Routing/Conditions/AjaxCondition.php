<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Routing\Conditions;

	use WPEmerge\Contracts\ConditionInterface;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\UrlableInterface;


	class AjaxCondition implements ConditionInterface, UrlableInterface {

		/**
		 * Ajax action to check against.
		 *
		 * @var string
		 */
		private $action = '';

		/**
		 * Flag whether to check against ajax actions which run for authenticated users.
		 *
		 * @var boolean
		 */
		private $private;

		/**
		 * Flag whether to check against ajax actions which run for unauthenticated users.
		 *
		 * @var boolean
		 */
		private $public;


		public function __construct( string $action, $private = true, $public = false ) {

			$this->action  = $action;
			$this->private = $private;
			$this->public  = $public;
		}


		private function matchesPrivateRequirement() : bool {

			return $this->private && is_user_logged_in();
		}


		private function matchesPublicRequirement() : bool {

			return $this->public && ! is_user_logged_in();
		}


		private function matchesActionRequirement( RequestInterface $request ) : bool {

			return $this->action === $request->body( 'action', $request->query( 'action' ) );
		}


		public function isSatisfied( RequestInterface $request ) : bool {

			if ( ! wp_doing_ajax() ) {
				return false;
			}

			if ( ! $this->matchesActionRequirement( $request ) ) {
				return false;
			}

			return $this->matchesPrivateRequirement() || $this->matchesPublicRequirement();
		}

		public function getArguments( RequestInterface $request ) : array {

			return [ 'action' => $this->action ];
		}


		public function toUrl( $arguments = [] ) : string {

			return add_query_arg( 'action', $this->action, self_admin_url( 'admin-ajax.php' ) );
		}

	}
