<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Factories\DynamicHooksFactory;
    use WPEmerge\Http\Request;

    class LoadedWpAdmin extends ApplicationEvent {


		public function __construct( Request $request, DynamicHooksFactory $hook_factory ) {


			$hook_factory->create($request);


		}



	}