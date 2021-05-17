<?php


    declare(strict_types = 1);


    namespace WPEmerge\Blade;


    use Illuminate\View\View as IlluminateView;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\ExceptionHandling\Exceptions\ViewException;
    use WPEmerge\Support\Arr;

    class BladeView implements ViewInterface
    {

        /**
         * @var IlluminateView
         */
        private $illuminate_view;

        public function __construct(IlluminateView $illuminate_view)
        {
            $this->illuminate_view = $illuminate_view;
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
                    'Error rendering view:['.$this->name().']'.PHP_EOL. $e->getMessage() . PHP_EOL. $e->getTraceAsString()
                );

            }

        }

        /**
         * Add a piece of data to the view.
         *
         * @param  string|array  $key
         * @param  mixed  $value
         * @return $this
         */
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

        public function render() :string
        {
           return $this->illuminate_view->render();
        }

        public function getData() :array
        {
            return $this->context();
        }

        public function toHtml()
        {
            return $this->illuminate_view->toHtml();
        }

    }