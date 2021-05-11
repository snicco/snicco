<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Contracts\ViewInterface;

	class MakingView extends ApplicationEvent {

		/**
		 * @var \WPEmerge\Contracts\ViewInterface
		 */
		private $view;

		public function __construct(ViewInterface $view) {

			$this->view = $view;

		}

		public function payload() : ViewInterface {

			return $this->view;

		}

	}