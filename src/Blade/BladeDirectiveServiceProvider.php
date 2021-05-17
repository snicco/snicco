<?php


    declare(strict_types = 1);


    namespace WPEmerge\Blade;

    use Illuminate\Support\Facades\Blade;
    use WPEmerge\Application\ApplicationTrait;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Facade\WP;

    class BladeDirectiveServiceProvider extends ServiceProvider
    {

        public function register() : void
        {
           //
        }

        function bootstrap() : void
        {

            Blade::if('auth', function () {

                return WP::isUserLoggedIn();

            });

            Blade::if('guest', function () {

                return ! WP::isUserLoggedIn();

            });

            Blade::if('role', function ($expression) {

                if ($expression === 'admin') {

                    $expression = 'administrator';

                }

                return WP::userIs($expression);

            });

            Blade::directive('service', function ($expression) {

                $segments = explode(',', preg_replace("/[()]/", '', $expression));

                $variable = trim($segments[0], " '\"");

                $service = trim($segments[1]);

                $app = $this->container->make(ApplicationTrait::class);

                $php = "<?php \${$variable} = {$app}::resolve({$service}::class); ?>";

                return $php;

            });


        }

    }