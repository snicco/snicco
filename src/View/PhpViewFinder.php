<?php


    declare(strict_types = 1);


    namespace WPEmerge\View;

    use Symfony\Component\Finder\Finder;
    use WPEmerge\Contracts\ViewFinderInterface;
    use WPEmerge\Support\FilePath;

    class PhpViewFinder implements ViewFinderInterface
    {

        /**
         * Custom views to search in. Will be searched recursively
         *
         * @param  string[]  $directories
         */
        private $directories;

        /**
         * @var string
         */
        private $search_depth;

        public function __construct(array $directories = [], int $depth = 2)
        {

            $this->search_depth = strval($depth + 1);
            $this->directories = $this->normalize($directories);

        }

        private function normalize(array $directories) : array
        {

            return array_filter(array_map([
                FilePath::class,
                'removeTrailingSlash',
            ], $directories));

        }

        public function exists(string $view_name) : bool
        {

            if (is_file($view_name)) {

                return true;

            }

            $finder = new Finder();
            $finder
                ->in($this->directories)
                ->files()
                ->depth('< '.$this->search_depth)
                ->ignoreUnreadableDirs()
                ->name(FilePath::ending($view_name, 'php'));

            return $finder->hasResults();

        }

        public function filePath( string $view_path ) : string
        {

            if ( is_file($view_path) ) {

                return $view_path;

            }

            $view_path = trim($view_path, '/');

            $finder = new Finder();
            $finder
                ->in($this->directories)
                ->files()
                ->depth('< '.$this->search_depth)
                ->ignoreUnreadableDirs()
                ->name(FilePath::ending($view_path, 'php'));

            if ( ! $finder->hasResults()) {

                return '';

            }

            foreach ($finder as $file) {
                break;
            }

            return $file->getRealPath();

        }

        public function includeFile(string $path, $context)
        {

            extract($context, EXTR_OVERWRITE);
            include $path;

        }


    }
