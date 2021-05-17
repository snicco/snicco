<?php


    declare(strict_types = 1);


    namespace WPEmerge\Blade;

    use Illuminate\View\View;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\ExceptionHandling\Exceptions\ViewException;
    use WPEmerge\Support\Arr;

    class BladeView extends View implements ViewInterface
    {

        public function toResponsable() : string
        {

            return $this->toString();
        }

        public function toString() : string
        {

            try {
                return $this->toHtml();
            }

            catch (\Throwable $e) {

                throw new ViewException(
                    'Error rendering view:['.$this->name().']'.PHP_EOL.$e->getMessage()
                );

            }

        }

        public function with($key, $value = null) : ViewInterface
        {

            return parent::with($key, $value);

        }

        // public function gatherData() : array
        // {
        //
        //     return $this->data ?? [];
        // }

        public function context(string $key = null, $default = null)
        {
            if ( $key === null ) {
                return $this->gatherData();
            }

            return Arr::get( $this->gatherData(), $key, $default );
        }


    }