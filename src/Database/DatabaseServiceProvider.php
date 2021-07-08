<?php


    declare(strict_types = 1);


    namespace BetterWP\Database;

    use BetterWP\Contracts\ServiceProvider;
    use BetterWP\Database\Contracts\BetterWPDbInterface;
    use BetterWP\Database\Contracts\ConnectionResolverInterface;
    use BetterWP\Database\Illuminate\DispatcherAdapter;
    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use Illuminate\Contracts\Events\Dispatcher as IlluminateEventDispatcher;
    use Illuminate\Database\Eloquent\Model as Eloquent;

    class DatabaseServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->bindConfig();
            $this->bindIlluminateDispatcher();
            $this->bindConnectionResolver();
            $this->bindWPDb();

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

            if ( ! isset($this->container[BetterWPDbInterface::class] ) ) {

                // Class names only.
                $this->container->instance(BetterWPDbInterface::class, BetterWPDb::class);

            }

        }

    }