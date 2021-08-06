<?php


    declare(strict_types = 1);


    namespace Snicco\Factories;

    use Closure;
    use Contracts\ContainerAdapter;
    use Exception;
    use Illuminate\Support\Reflector;
    use RuntimeException;
    use Snicco\Contracts\Handler;
    use Snicco\Support\Str;
    use Snicco\Traits\ReflectsCallable;

    use function collect;

    abstract class AbstractFactory
    {

        use ReflectsCallable;

        /**
         * Array of FQN from where we look for the class
         * being built
         */
        protected array            $namespaces;
        protected ContainerAdapter $container;

        public function __construct(array $namespaces, ContainerAdapter $container)
        {

            $this->namespaces = $namespaces;
            $this->container = $container;

        }

        /**
         * @param  string|array|callable  $raw_handler
         *
         * @return Handler
         * @throws Exception
         */
        abstract public function createUsing($raw_handler) : Handler;

        protected function normalizeInput($raw_handler) : array
        {

            return collect($raw_handler)
                ->flatMap(function ($value) {

                    if ($value instanceof Closure || ! Str::contains($value, '@')) {

                        return [$value];

                    }

                    return [Str::before($value, '@'), Str::after($value, '@')];

                })
                ->filter(fn($value) => ! empty($value))
                ->values()
                ->all();

        }

        protected function checkIsCallable(array $handler) : ?array
        {

            if (Reflector::isCallable($handler)) {

                return $handler;

            }

            if (count($handler) === 1 && method_exists($handler[0], '__invoke')) {

                return [$handler[0], '__invoke'];

            }

            [$class, $method] = $handler;

            $matched = collect($this->namespaces)
                ->map(function ($namespace) use ($class, $method) {

                    if (Reflector::isCallable([$namespace.'\\'.$class, $method])) {

                        return [$namespace.'\\'.$class, $method];

                    }

                })
                ->filter(fn($value) => $value !== null);

            return $matched->isNotEmpty() ? $matched->first() : null;


        }

        protected function fail($class, $method)
        {
            $method = Str::replaceFirst('@', '', $method);

            throw new RuntimeException(
                "[".$class.", '".$method."'] is not a valid callable."
            );

        }

        protected function wrapClosure(Closure $closure) : Closure
        {
            return fn($args) => $this->container->call($closure, $args);
        }

        protected function wrapClass(array $controller) : Closure
        {
            return fn($args) => $this->container->call(implode('@', $controller), $args);
        }


    }