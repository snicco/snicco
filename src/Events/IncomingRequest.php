<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Support\Str;

    class IncomingRequest extends ApplicationEvent {


		/**
		 * @var Request
		 */
		public $request;


        /**
         * @var bool
         */
        protected $has_matching_route = false;

        public function __construct( Request $request) {

			$this->request = $request->withType( static::class );

		}

        public function matchedRoute()
        {

            $this->has_matching_route = true;

        }

        protected function isNativeWordpressJsonApiRequest () : bool
        {

            return Str::contains($this->request->path(), 'wp-json');

        }
    }