<?php


    declare(strict_types = 1);


    namespace WPEmerge\Blade;

    use Illuminate\Support\Facades\Blade;
    use Illuminate\Support\HtmlString;
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

            Blade::directive('csrf', function ($expression) {

                $expression = preg_replace('/\s/', '',$expression);

                $segments = explode(',',$expression);

                $action = $segments[0];
                $name = $segments[1];

                return "<?php wp_nonce_field({$action},{$name}); ?>";


            });

            Blade::directive('method', function ($method) {

                // $method = trim("'", $method);

                $html = new HtmlString("<input type='hidden' name='_method' value={$method}>");

                return "<?php echo \"{$html->toHtml()}\";";


            });



        }

    }