<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Factories\DynamicHooksFactory;

	class LoadedWpAdmin extends ApplicationEvent {


		public function __construct( RequestInterface $request, DynamicHooksFactory $hook_factory ) {


			$hook_factory->create($request);


		}



	}