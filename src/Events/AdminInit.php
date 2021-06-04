<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use Psr\Http\Message\ServerRequestInterface;
    use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Factories\DynamicHooksFactory;

    class AdminInit extends ApplicationEvent {


		public function __construct( ServerRequestInterface $request, DynamicHooksFactory $hook_factory ) {

			$hook_factory->create($request);

		}



	}