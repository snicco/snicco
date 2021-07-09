<?php


    declare(strict_types = 1);


    namespace BetterWP\Database;

    use BetterWP\Contracts\ServiceProvider;
    use BetterWP\Database\Contracts\BetterWPDbInterface;
    use BetterWP\Database\Contracts\ConnectionResolverInterface;
    use BetterWP\Database\Contracts\WPConnectionInterface;
    use BetterWP\Database\Illuminate\DispatcherAdapter;
    use BetterWP\Database\Illuminate\MySqlSchemaBuilder;
    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use Illuminate\Contracts\Container\Container;
    use Illuminate\Contracts\Events\Dispatcher as IlluminateEventDispatcher;
    use Illuminate\Database\Eloquent\Model as Eloquent;
    use Illuminate\Support\Facades\Facade;
    use Illuminate\Container\Container as IlluminateContainer;
    use SniccoAdapter\BaseContainerAdapter;

    class DatabaseServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindConfig();
            $this->bindIlluminateDispatcher();
            $this->bindConnectionResolver();
            $this->bindWPDb();
            $this->bindSchemaBuilder();
            $this->bindFacades();
            $this->bindWPConnection();

        }

        function bootstrap() : void
        {

            $this->bootEloquent();
        }

        private function bindIlluminateDispatcher()
        {

            $this->container->singleton(IlluminateEventDispatcher::class, function () {

                return new DispatcherAdapter($this->container->make(WordpressDispatcher::class));
            });
            $this->container->singleton('events', function () {

                return $this->container->make(IlluminateEventDispatcher::class);
            });

        }

        private function bootEloquent()
        {

            Eloquent::setEventDispatcher($this->container->make(IlluminateEventDispatcher::class));
            Eloquent::setConnectionResolver($this->container->make(ConnectionResolverInterface::class));
        }

        private function bindConnectionResolver()
        {

            $this->container->singleton(ConnectionResolverInterface::class, function () {

                $connections = $this->config->get('database.connections');

                $r = new WPConnectionResolver($connections, $this->container->make(BetterWPDbInterface::class));
                $r->setDefaultConnection('wp_connection');

                return $r;

            });
        }

        private function bindConfig()
        {

            $this->config->extend('database.connections', [

                'wp_connection' => [
                    'username' => DB_USER,
                    'database' => DB_NAME,
                    'password' => DB_PASSWORD,
                    'host' => DB_HOST,
                ],

            ]);
        }

        private function bindWPDb()
        {

            if ( ! isset($this->container[BetterWPDbInterface::class])) {

                // Class names only.
                $this->container->instance(BetterWPDbInterface::class, BetterWPDb::class);

            }

        }

        private function bindSchemaBuilder()
        {

            $this->container->singleton(MySqlSchemaBuilder::class, function () {

                return new MySqlSchemaBuilder($this->resolveConnection());
            });

        }

        private function bindFacades()
        {

            $container = $this->parseContainer();
            Facade::setFacadeApplication($container);
            IlluminateContainer::setInstance($container);

            $this->container->singleton('db', function () {

                return $this->container->make(ConnectionResolverInterface::class);


            });

        }

        private function parseContainer() : Container
        {

            return $this->container instanceof BaseContainerAdapter
                ? $this->container->implementation()
                : IlluminateContainer::getInstance();

        }

        private function resolveConnection(string $name = null)
        {

            return $this->container->make(ConnectionResolverInterface::class)->connection($name);

        }

        private function bindWPConnection()
        {

            $this->container->singleton(WPConnectionInterface::class, function () {

                return function (string $name = null) {

                    return $this->resolveConnection($name);

                };

            });
        }

    }