<?php


	namespace WPEmerge\Events;

	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Factories\DynamicHooksFactory;

	class LoadedWpAdmin extends ApplicationEvent {



		public function __construct( RequestInterface $request, DynamicHooksFactory $factory ) {


			$factory->create($request);


		}



	}