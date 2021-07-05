<?php


    declare(strict_types = 1);


    namespace BetterWP\Application;

    use Contracts\ContainerAdapter;
    use LogicException;
    use Tests\unit\View\MethodField;
    use BetterWP\Contracts\AbstractRedirector;
    use BetterWP\Http\Cookies;
    use BetterWP\Http\ResponseFactory;
    use BetterWP\Http\Responses\RedirectResponse;
    use BetterWP\Mail\MailBuilder;
    use BetterWP\Routing\Route;
    use BetterWP\Routing\Router;
    use BetterWP\Routing\UrlGenerator;
    use BetterWP\Contracts\ViewInterface;
    use BetterWP\Session\CsrfField;
    use BetterWP\Session\Session;
    use BetterWP\View\GlobalContext;

    /**
     * Can be applied to your App class via a "@mixin" annotation for better IDE support.
     * This class is not meant to be used in any other way.
     *
     * @codeCoverageIgnore
     */
    final class ApplicationMixin
    {

        private function __construct()
        {
        }

        /**
         *
         * Resolve an item from the applications config.
         *
         * @param  string  $key
         * @param  mixed  $default
         *
         * @return mixed|ApplicationConfig
         */
        public static function config(?string $key = null, $default = null)
        {
        }

        /**
         * Return the application session store.
         *
         * @return Session
         */
        public static function session() : Session
        {
        }

        /**
         *
         * Get the applications UrlGenerator instance
         *
         * @return UrlGenerator
         */
        public static function url() : UrlGenerator
        {

        }

        /**
         * Returns a redirect response object if the path is set.
         * If not returns an instance of the bound Redirector
         *
         * @see AbstractRedirector
         *
         * @param  string|null  $path
         * @param  int  $status
         * @return RedirectResponse|AbstractRedirector
         */
        public static function redirect(?string $path = null , int $status = 302)
        {

        }

        /**
         * @return MailBuilder
         */
        public static function mail() :MailBuilder
        {


        }

        /**
         * Creates hidden csrf input fields based on the current user session.
         * If a csrf token is present in the session its used if not a new hash is created and saved
         * in the session.
         *
         * Does NOT echo the output but returns the html as a string.
         *
         * @see CsrfField::asHtml()
         *
         * @return string
         */
        public static function csrfField() : string
        {

        }

        public static function csrf() : CsrfField
        {

        }

        /**
         * Bootstrap the application.
         *
         * @return void
         * @see Application::boot()
         */
        public static function boot(bool $load = true )
        {
        }

        /**
         * Get the IoC container instance.
         *
         * @return ContainerAdapter
         */
        public static function container() : ContainerAdapter
        {
        }

        /**
         * Set the IoC container instance.
         *
         * @param  ContainerAdapter  $container
         *
         * @return void
         */
        public static function setContainer(ContainerAdapter $container)
        {
        }

        /**
         * Resolve a dependency from the IoC container.
         *
         * @param  string  $key
         *
         * @return mixed|null
         * @see HasContainer::resolve()
         */
        public static function resolve(string $key)
        {
        }

        /**
         * Get the Application instance.
         *
         * @return Application
         */
        public static function app() : Application
        {
        }

        /**
         * Returns a response factory instance.
         *
         * @return  ResponseFactory
         * @see \BetterWP\Http\ResponseFactory
         */
        public static function response() : ResponseFactory
        {
        }

        /**
         * Create a view
         *
         * @param  string|string[]  $views
         *
         * @return ViewInterface
         * @see    \BetterWP\View\ViewFactory::make()
         */
        public static function view($views) : ViewInterface
        {
        }

        /**
         * Output child layout content.
         *
         * @return void
         * @see    \BetterWP\View\PhpViewEngine::includeNextView()
         */
        public static function includeChildViews() : void
        {
        }

        /**
         * Output the specified view.
         *
         * @param  string|string[]  $views
         * @param  array<string, mixed>  $context
         *
         * @return string
         * @see    \BetterWP\Contracts\ViewInterface::toString()
         * @see    \BetterWP\View\ViewFactory::make()
         */
        public static function render($views, array $context = []) : string
        {
        }

        /**
         *
         * Add a new view composer to the given views
         *
         * @param  string|string[]  $views
         * @param  string|array|callable|\Closure  $callable
         *
         * @return void
         * @see \BetterWP\View\ViewComposerCollection::addComposer()
         */
        public static function addComposer($views, $callable) : void
        {
        }

        /**
         *
         * Returns the global variable bag used by view composers.
         *
         * Arrays are converted to instances of VariableBag.
         *
         * @see GlobalContext::add()
         */
        public static function globals(string $name, $context)
        {
        }

        /**
         * Return the response cookies instance
         *
         * @return Cookies
         */
        public static function cookies() : Cookies
        {


        }

        /**
         * Create a new route.
         *
         * @return Router
         */
        public static function route() : Router
        {
        }

        /**
         * Get the url to a named route
         *
         * @see UrlGenerator::toRoute()
         */
        public static function routeUrl(string $route_name, array $arguments = [], bool $secure = true, bool $absolute = true) : string
        {
        }

        /**
         * Create a new post route
         *
         * @see Router::post()
         */
        public static function post(string $url = '*', $action = null) : Route
        {


        }

        /**
         * Create a new get route
         *
         * @see Router::get()
         */
        public static function get(string $url = '*', $action = null) : Route
        {


        }

        /**
         * Create a new patch route
         *
         * @see Router::patch()
         */
        public static function patch(string $url = '*', $action = null) : Route
        {


        }

        /** Create a new put route
         *
         * @see Router::put()
         */
        public static function put(string $url = '*', $action = null) : Route
        {
        }

        /**
         * Create a new options route
         *
         * @see Router::options()
         */
        public static function options(string $url = '*', $action = null) : Route
        {
        }

        /**
         * Create a new delete route
         *
         * @see Router::delete()
         */
        public static function delete(string $url = '*', $action = null) : Route
        {
        }

        /**
         * Get the HTML for a hidden method field that can be used in HTML
         * forms to override the POST method
         *
         *
         * @param  string  $method  accepted values are put,patch,delete
         *
         * @return string
         *
         * @throws LogicException
         *
         * @see MethodField::html()
         */
        public static function methodField(string $method) : string
        {

        }


    }
