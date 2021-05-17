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

        /**
         * @var ViewFinderInterface
         */
        private $finder;

        public function __construct(Factory $view_factory)
        {
            $this->view_factory = $view_factory;
            $this->finder = $view_factory->getFinder();
        }

        /**
         * @param  string|string[]  $views
         *
         * @return BladeView
         * @throws ViewNotFoundException
         */
        public function make($views) : ViewInterface
        {
            $view = Arr::firstEl($views);


            try {

                $path = $this->finder->find(
                    $view = $this->normalizeName($view)
                );

                $engine = $this->view_factory->getEngineFromPath($path);

                return new BladeView($this->view_factory, $engine, $view, $path);

            }
            catch (\Throwable $e ) {

                throw new ViewNotFoundException(
                    'It was not possible to create view: [' . $view . '] with the blade engine.' . PHP_EOL . $e->getMessage()
                );

            }



        }

        /**
         * Normalize a view name.
         *
         * @param  string  $name
         * @return string
         */
        private function normalizeName(string $name) :string
        {
            return ViewName::normalize($name);
        }

    }