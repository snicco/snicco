<?php


	declare( strict_types = 1 );


	namespace Snicco\Events;

	use Snicco\Events\Event;
	use Snicco\Contracts\ViewInterface;

	class MakingView extends Event {

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