<?php


    declare(strict_types = 1);


    namespace Snicco\Blade;


    use Illuminate\Contracts\View\View as IlluminateViewContract;
    use Illuminate\View\View as IlluminateView;
    use Snicco\Contracts\ViewInterface;
    use Snicco\Events\MakingView;
    use Snicco\ExceptionHandling\Exceptions\ViewException;
    use Snicco\Support\Arr;
    use Throwable;

    class BladeView implements ViewInterface, IlluminateViewContract
    {

        /**
         * @var IlluminateView|IlluminateViewContract
         */
        private $illuminate_view;

        public function __construct($illuminate_view)
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

                MakingView::dispatch([$this]);

                return $this->illuminate_view->render();

            }

            catch (Throwable $e) {

                throw new ViewException(
                    'Error rendering view:['.$this->name().']'.PHP_EOL.$e->getMessage().PHP_EOL.$e->getTraceAsString()
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

            return $this->toString();
        }

        public function getData() :array
        {
            return $this->context();
        }

        public function path()
        {
           return $this->illuminate_view->getPath();
        }

    }