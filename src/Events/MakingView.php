<?php


	declare( strict_types = 1 );


	namespace Snicco\Events;

	use Snicco\Contracts\ViewInterface;

    class MakingView extends Event {

		private ViewInterface $view;

		public function __construct(ViewInterface $view) {

			$this->view = $view;

		}

		public function payload() : ViewInterface {

			return $this->view;

		}

	}