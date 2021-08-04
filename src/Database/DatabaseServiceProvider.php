<?php


    declare(strict_types = 1);


    namespace Snicco\Database;

    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use Faker\Factory;
    use Faker\Generator as FakerGenerator;
    use Illuminate\Contracts\Events\Dispatcher as IlluminateEventDispatcher;
    use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
    use Illuminate\Database\Eloquent\Model as Eloquent;
    use Snicco\Contracts\ServiceProvider;
    use Snicco\Database\Contracts\BetterWPDbInterface;
    use Snicco\Database\Contracts\ConnectionResolverInterface;
    use Snicco\Database\Contracts\WPConnectionInterface;
    use Snicco\Database\Illuminate\IlluminateDispatcherAdapter;
    use Snicco\Database\Illuminate\MySqlSchemaBuilder;
    use Snicco\Traits\ReliesOnIlluminateContainer;

    class DatabaseServiceProvider extends ServiceProvider
    {

        use ReliesOnIlluminateContainer;

        public function register() : void
        {

            $this->bindConfig();
            $this->bindIlluminateDispatcher();
            $this->bindConnectionResolver();
            $this->bindWPDb();
            $this->bindSchemaBuilder();
            $this->bindFacades();
            $this->bindWPConnection();
            $this->bindDatabaseFactory();


        }

        function bootstrap() : void
        {

            $this->bootEloquent();
        }

        private function bindIlluminateDispatcher()
        {

            $this->container->singleton(IlluminateEventDispatcher::class, function () {

                return new IlluminateDispatcherAdapter($this->container->make(WordpressDispatcher::class));

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

            $this->setFacadeContainer($c = $this->parseIlluminateContainer());
            $this->setGlobalContainerInstance($c);

            $this->container->singleton('db', function () {

                return $this->container->make(ConnectionResolverInterface::class);


            });

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

        private function bindDatabaseFactory()
        {

            $this->container->singleton(FakerGenerator::class, function () {

                $locale = $this->config->get('database.faker_locale', 'en_US');

                $faker = Factory::create($locale);
                $faker->unique(true);

                return $faker;

            });

            EloquentFactory::guessFactoryNamesUsing(function (string $model) {

                $namespace = $this->config->get('database.factory_namespace', 'Database\\Factories\\');
                $model = class_basename($model);

                return $namespace.$model.'Factory';
            });

            EloquentFactory::guessModelNamesUsing(function ($factory) {

                $namespace = $this->config->get('database.model_namespace', 'App\\Models\\');
                $model = class_basename($factory);

                return str_replace('Factory', '', $namespace.$model);

            });

        }

    }