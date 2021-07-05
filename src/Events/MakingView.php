<?php


	declare( strict_types = 1 );


	namespace BetterWP\Events;

	use BetterWP\Application\ApplicationEvent;
	use BetterWP\Contracts\ViewInterface;

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