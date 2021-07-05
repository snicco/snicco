<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use WPMvc\Contracts\PhpViewInterface;
	use WPMvc\Contracts\ViewInterface;
	use WPMvc\Support\Arr;

	class TestView implements PhpViewInterface {

		private $context = [];

		private $name;

		public function __construct(string $name) {

			$this->name = $name;
		}

		public function context( string $key = null, $default = null ) {

			if ( $key === null ) {
				return $this->context;
			}

			return Arr::get( $this->context, $key, $default );

		}

		public function with( $key, $value = null ) :ViewInterface {

			if ( is_array( $key ) ) {
				$this->context = array_merge( $this->context(), $key );
			} else {
				$this->context[ $key ] = $value;
			}

			return  $this;

		}

		public function toResponsable() {

		    return $this->toString();

		}

		public function toString() : string {

		    $context = '[';

            foreach ($this->context as $key => $value ) {

                $context .= $key .  '=>' . $value . ',';

            }
            $context = rtrim($context, ',');
            $context .= ']';

		    return 'VIEW:' . $this->name . ',CONTEXT:'. $context;

		}

		public function path() : string {

		}

		public function parent() : ?PhpViewInterface {

		}

		public function name() : string {

			return $this->name;

		}

	}