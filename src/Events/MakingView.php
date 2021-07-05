<?php


	declare( strict_types = 1 );


	namespace WPMvc\Events;

	use WPMvc\Application\ApplicationEvent;
	use WPMvc\Contracts\ViewInterface;

	class MakingView extends ApplicationEvent {

		/**
		 * @var ViewInterface
		 */
		private $view;

		public function __construct(ViewInterface $view) {

			$this->view = $view;

		}

		public function payload() : ViewInterface {

			return $this->view;

		}

	}