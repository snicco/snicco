<?php


    declare(strict_types = 1);


    namespace WPEmerge\Events;

    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Http\Psr7\Request;

    class RoutesLoadable extends ApplicationEvent
    {

        /**
         * @var Request
         */
        public $request;

        /**
         * @var string|null
         */
        public $template;

        public function __construct(Request $request, ?string $template = null )
        {
            $this->request = $request;
            $this->template = $template;
        }

    }