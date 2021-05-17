<?php


    declare(strict_types = 1);


    namespace WPEmerge\Blade;

    use Illuminate\View\View;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\ExceptionHandling\Exceptions\ViewException;
    use WPEmerge\Support\Arr;

    class BladeView  implements ViewInterface
    {

        /**
         * @var View
         */
        private $illuminate_view;

        public function __construct(View $illuminate_view)
        {
            $this->illuminate_view = $illuminate_view;
            $this->removeIlluminateContext();
        }

        public function toResponsable() : string
        {

            return $this->toString();
        }

        public function toString() : string
        {

            try {
                return $this->illuminate_view->toHtml();
            }

            catch (\Throwable $e) {

                throw new ViewException(
                    'Error rendering view:['.$this->name().']'.PHP_EOL.$e->getMessage()
                );

            }

        }

        public function with($key, $value = null) : ViewInterface
        {
             $this->illuminate_view->with($key, $value);
             return $this;
        }

        public function context(string $key = null, $default = null)
        {
            if ( $key === null ) {
                return $this->illuminate_view->getData();
            }

            return Arr::get( $this->illuminate_view->getData(), $key, $default );
        }

        public function name() :string
        {
           return $this->illuminate_view->name();
        }

        private function removeIlluminateContext () {

            $this->illuminate_view->with('app', null);
            $this->illuminate_view->with('__env', null);

        }
    }