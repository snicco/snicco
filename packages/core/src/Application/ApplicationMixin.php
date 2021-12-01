<?php /** @noinspection PhpInconsistentReturnPointsInspection */

declare(strict_types=1);

namespace Snicco\Application;

use Closure;
use LogicException;
use Snicco\Routing\Route;
use Snicco\Routing\Router;
use Snicco\Session\Session;
use Snicco\Mail\MailBuilder;
use Snicco\Http\MethodField;
use Snicco\Session\CsrfField;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;
use Snicco\Routing\UrlGenerator;
use Snicco\Contracts\Redirector;
use Snicco\View\GlobalViewContext;
use Snicco\Shared\ContainerAdapter;
use Snicco\View\Contracts\ViewInterface;
use Snicco\Http\Responses\RedirectResponse;

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
     * Resolve an item from the applications' config.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     *
     * @return mixed|Config
     */
    public static function config(?string $key = null, $default = null)
    {
    }
    
    /**
     * Returns the local path to the project root
     */
    public static function basePath() :string
    {
    }
    
    /**
     * Returns the local path to the dist folder
     *
     * @param  string  $path  A path relative to the dist folder
     *
     * @return string
     */
    public static function distPath(string $path = '') :string
    {
    }
    
    /**
     * Returns the local path to the storage folder
     *
     * @param  string  $path  A path relative to the storage folder
     *
     * @return string
     */
    public static function storagePath(string $path = '') :string
    {
    }
    
    /**
     * Returns the local path to the config folder
     *
     * @param  string  $path  A path relative to the config folder
     *
     * @return string
     */
    public static function configPath(string $path = '') :string
    {
    }
    
    public static function environment() :bool
    {
    }
    
    public static function isLocal() :bool
    {
    }
    
    public static function isProduction() :bool
    {
    }
    
    public static function isRunningInConsole() :bool
    {
    }
    
    /**
     * Return the currently active session instance
     *
     * @return Session
     */
    public static function session() :Session
    {
    }
    
    /**
     * Returns the current request going through the application.
     *
     * @note DO NOT USE THIS FUNCTION ANYWHERE BESIDES INSIDE YOUR CONTROLLERS OR VIEWS.
     * MAKE SURE YOU ARE FAMILIAR WITH HOW PSR7 REQUEST IMMUTABILITY WORKS.
     * @return Request
     */
    public static function request() :Request
    {
    }
    
    /**
     * Get the applications UrlGenerator instance
     *
     * @return UrlGenerator
     */
    public static function url() :UrlGenerator
    {
    }
    
    /**
     * Returns a redirect response object if the path is set.
     * If not returns an instance of the bound Redirector
     *
     * @param  string|null  $path
     * @param  int  $status
     *
     * @return RedirectResponse|Redirector
     * @see Redirector
     */
    public static function redirect(?string $path = null, int $status = 302)
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
     * Does NOT echo the output but returns the html as a string.
     *
     * @return string
     * @see CsrfField::asHtml()
     */
    public static function csrfField() :string
    {
    }
    
    public static function csrf() :CsrfField
    {
    }
    
    /**
     * Bootstrap the application.
     *
     * @return void
     * @see Application::boot()
     */
    public static function boot(bool $load = true)
    {
    }
    
    /**
     * Get the IoC container instance.
     *
     * @return ContainerAdapter
     */
    public static function container() :ContainerAdapter
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
    public static function app() :Application
    {
    }
    
    /**
     * Returns a response factory instance.
     *
     * @return  ResponseFactory
     * @see \Snicco\Http\ResponseFactory
     */
    public static function response() :ResponseFactory
    {
    }
    
    /**
     * Create a view
     *
     * @param  string|string[]  $views
     *
     * @return ViewInterface
     */
    public static function view($views) :ViewInterface
    {
    }
    
    /**
     * Output the specified view.
     *
     * @param  string|string[]  $views
     * @param  array<string, mixed>  $context
     *
     * @return string
     * @see    \Snicco\View\Contracts\ViewInterface::toString()
     */
    public static function render($views, array $context = []) :string
    {
    }
    
    /**
     * Add a new view composer to the given views
     *
     * @param  string|string[]  $views
     * @param  string|array|callable|Closure  $callable
     *
     * @return void
     * @see \Snicco\View\ViewComposerCollection::addComposer()
     */
    public static function addComposer($views, $callable) :void
    {
    }
    
    /**
     * Returns the global variable bag available to all views.
     * Arrays are converted to an instance of Repository.
     * Returns the global context if no args are passed
     *
     * @see GlobalViewContext::add()
     */
    public static function globals(?string $name = null, $context = null) :GlobalViewContext
    {
    }
    
    /**
     * Create a new route.
     *
     * @return Router
     */
    public static function route() :Router
    {
    }
    
    /**
     * Get the url to a named route
     *
     * @see UrlGenerator::toRoute()
     */
    public static function routeUrl(string $route, array $arguments = [], bool $secure = true, bool $absolute = true) :string
    {
    }
    
    /**
     * Create a new post route
     *
     * @see Router::post()
     */
    public static function post(string $url = '*', $action = null) :Route
    {
    }
    
    /**
     * Create a new get route
     *
     * @see Router::get()
     */
    public static function get(string $url = '*', $action = null) :Route
    {
    }
    
    /**
     * Create a new patch route
     *
     * @see Router::patch()
     */
    public static function patch(string $url = '*', $action = null) :Route
    {
    }
    
    /** Create a new put route
     *
     * @see Router::put()
     */
    public static function put(string $url = '*', $action = null) :Route
    {
    }
    
    /**
     * Create a new options route
     *
     * @see Router::options()
     */
    public static function options(string $url = '*', $action = null) :Route
    {
    }
    
    /**
     * Create a new delete route
     *
     * @see Router::delete()
     */
    public static function delete(string $url = '*', $action = null) :Route
    {
    }
    
    /**
     * Get the HTML for a hidden method field that can be used in HTML
     * forms to override the POST method
     *
     * @param  string  $method  accepted values are put,patch,delete
     *
     * @return string
     * @throws LogicException
     * @see MethodField::html()
     */
    public static function methodField(string $method) :string
    {
    }
    
    /**
     * Register an alias.
     * If no method is provided the alias will resolve the target from the container and return it.
     * If a method is provided the target will be resolved an the method will be called on it.
     *
     * @param  string  $alias
     * @param  string|Closure  $target
     * @param  string  $method
     *
     * @see Application::alias()
     */
    public function alias(string $alias, $target, string $method = '')
    {
    }
    
}
