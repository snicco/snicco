<?php


    declare(strict_types = 1);


    namespace WPEmerge\Blade;

    use Illuminate\View\Factory;
    use Illuminate\View\ViewFinderInterface;
    use Illuminate\View\ViewName;
    use WPEmerge\Contracts\ViewEngineInterface;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\ExceptionHandling\Exceptions\ViewNotFoundException;
    use WPEmerge\Support\Arr;

    class BladeEngine implements ViewEngineInterface
    {

        /**
         * @var Factory
         */
        private $view_factory;

        public function __construct(Factory $view_factory)
        {
            $this->view_factory = $view_factory;
        }

        /**
         * @param  string|string[]  $views
         *
         * @return BladeView
         * @throws ViewNotFoundException
         */
        public function make($views) : ViewInterface
        {

            try {

               $view = $this->view_factory->first(
                   $this->normalizeNames($views)
               );

                return new BladeView($view);

            }
            catch (\Throwable $e ) {

                throw new ViewNotFoundException(
                    'It was not possible to create a view from: [' . implode(',', Arr::wrap($views)) . '] with the blade engine.' . PHP_EOL . $e->getMessage()
                );

            }


        }

        /**
         * Normalize a view name.
         *
         * @param string|string[] $names
         *
         * @return array
         */
        private function normalizeNames( $names ) :array
        {

            return collect($names)->map(function ($name) {

                return ViewName::normalize($name);
            })->all();

        }

    }