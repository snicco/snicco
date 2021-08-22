<?php

declare(strict_types=1);

namespace Snicco\Testing;

use Mockery;
use RuntimeException;
use Snicco\Support\WP;
use Snicco\Support\Arr;
use Snicco\Events\Event;
use Snicco\Http\Delegate;
use Snicco\Routing\Route;
use Illuminate\Support\Str;
use Snicco\Http\HttpKernel;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;
use Snicco\Application\Config;
use Snicco\Contracts\Middleware;
use Snicco\Routing\RouteRegistrar;
use Illuminate\Container\Container;
use Snicco\Application\Application;
use Codeception\TestCase\WPTestCase;
use Snicco\Contracts\ServiceProvider;
use Illuminate\Support\Facades\Facade;
use Snicco\Contracts\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use Snicco\Testing\Concerns\TravelsTime;
use Snicco\Session\SessionServiceProvider;
use Mockery\Exception\InvalidCountException;
use Snicco\Contracts\AbstractRouteCollection;
use Snicco\Contracts\RouteRegistrarInterface;
use Snicco\Testing\Concerns\InteractsWithMail;
use Snicco\Testing\Concerns\MakesHttpRequests;
use Snicco\Testing\Concerns\InteractsWithSession;
use Snicco\ExceptionHandling\NullExceptionHandler;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Snicco\Testing\Concerns\InteractsWithContainer;
use Snicco\Testing\Concerns\InteractsWithAuthentication;
use Snicco\Testing\Concerns\InteractsWithWordpressUsers;

abstract class TestCase extends WPTestCase
{
    
    use MakesHttpRequests;
    use InteractsWithContainer;
    use InteractsWithSession;
    use InteractsWithAuthentication;
    use InteractsWithWordpressUsers;
    use InteractsWithMail;
    use TravelsTime;
    
    protected Application                   $app;
    
    protected ?Session                      $session       = null;
    
    protected Request                       $request;
    
    protected Config                        $config;
    
    protected ServerRequestFactoryInterface $request_factory;
    
    protected HttpKernel                    $kernel;
    
    protected bool                          $defer_boot    = false;
    
    protected bool                          $routes_loaded = false;
    
    /**
     * @var callable[]
     */
    protected array $after_config_loaded_callbacks = [];
    
    private bool    $set_up_has_run                = false;
    
    /**
     * @var Route[]
     */
    private array $additional_routes = [];
    
    /**
     * @var callable[]
     */
    private array $after_application_created_callbacks = [];
    
    /**
     * @var callable[]
     */
    private array $before_application_destroy_callbacks = [];
    
    /**
     * @return ServiceProvider[]
     */
    public function packageProviders() :array
    {
        
        return [];
    }
    
    /**
     * Return an instance of your Application. DON'T BOOT THE APPLICATION.
     */
    abstract protected function createApplication() :Application;
    
    protected function afterApplicationCreated(callable $callback)
    {
        $this->after_application_created_callbacks[] = $callback;
        if ($this->set_up_has_run) {
            $callback();
        }
    }
    
    protected function beforeApplicationDestroyed(callable $callback)
    {
        
        $this->before_application_destroy_callbacks[] = $callback;
    }
    
    protected function afterLoadingConfig(callable $callback)
    {
        
        $this->after_config_loaded_callbacks[] = $callback;
    }
    
    protected function setUp() :void
    {
        
        parent::setUp();
        
        $this->backToPresent();
        
        if ( ! isset($this->app)) {
            $this->refreshApplication();
        }
        
        $this->config = $this->app->config();
        
        foreach ($this->after_config_loaded_callbacks as $callback) {
            $callback();
        }
        
        $this->config->extend('app.providers', $this->packageProviders());
        $this->request_factory = $this->app->resolve(ServerRequestFactoryInterface::class);
        $this->replaceBindings();
        
        if ($this->set_up_has_run) {
            $this->fail('TestCase booted twice');
        }
        
        $this->bindRequest();
        $this->setUpTraits();
        $this->setProperties();
        
        foreach ($this->after_application_created_callbacks as $callback) {
            $callback();
        }
        
        $this->set_up_has_run = true;
        
    }
    
    protected function refreshApplication()
    {
        $this->app = $this->createApplication();
    }
    
    protected function boot()
    {
    }
    
    protected function tearDown() :void
    {
        
        if ($this->app) {
            $this->callBeforeApplicationDestroyedCallbacks();
            unset($this->app);
        }
        
        $this->set_up_has_run = false;
        
        if (class_exists(Mockery::class)) {
            
            if ($container = Mockery::getContainer()) {
                
                $this->addToAssertionCount($container->mockery_getExpectationCount());
                
            }
            
            try {
                
                Mockery::close();
                
            } catch (InvalidCountException $e) {
                
                if ( ! Str::contains($e->getMethodName(), ['doWrite', 'askQuestion'])) {
                    throw $e;
                }
                
            }
        }
        
        if (class_exists(Facade::class)) {
            
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);
            
        }
        
        if (class_exists(Container::class)) {
            
            Container::setInstance();
            
        }
        
        $this->backToPresent();
        
        Event::setInstance(null);
        WP::reset();
        
        parent::tearDown();
        
    }
    
    protected function withReplacedConfig($items, $value) :TestCase
    {
        
        $items = is_array($items) ? $items : [$items => $value];
        
        foreach ($items as $key => $value) {
            
            $this->config->remove($key);
            
        }
        
        return $this->withAddedConfig($items);
        
    }
    
    protected function withAddedConfig($items, $value = null) :TestCase
    {
        
        $items = is_array($items) ? $items : [$items => $value];
        
        foreach ($items as $key => $value) {
            
            if (is_array($this->config->get($key))) {
                
                $this->config->extend($key, $value);
            }
            else {
                
                $this->config->set($key, $value);
                
            }
            
        }
        
        return $this;
        
    }
    
    protected function withAddedMiddleware(string $group, $middleware) :TestCase
    {
        
        $this->config->extend("middleware.groups.$group", Arr::wrap($middleware));
        
        return $this;
    }
    
    /**
     * Disable all or some middleware for the test.
     *
     * @param  string|array|null  $middleware
     *
     * @return $this
     */
    protected function withoutMiddleware($middleware = null) :TestCase
    {
        
        if (is_null($middleware)) {
            
            $this->app->config()->set('middleware.disabled', true);
            
            return $this;
            
        }
        
        foreach ((array) $middleware as $abstract) {
            
            if ( ! class_exists($abstract)) {
                
                $aliases = $this->config->get('middleware.aliases');
                $m = $aliases[$abstract] ?? null;
                
                if ($m === null) {
                    throw new RuntimeException(
                        "You are trying to disable middleware [$abstract] which does not seem to exist."
                    );
                }
                
                $abstract = $m;
                
            }
            
            $this->app->container()->singleton($abstract, function () {
                
                return new class extends Middleware
                {
                    
                    public function handle(Request $request, Delegate $next) :ResponseInterface
                    {
                        
                        return $next($request);
                    }
                    
                };
            });
            
        }
        
        return $this;
    }
    
    /**
     * Enable the given middleware for the test.
     * Middleware has to be an object
     *
     * @param  object|object[]|null  $middleware
     *
     * @return $this
     */
    protected function withMiddleware($middleware = null) :TestCase
    {
        
        if (is_null($middleware)) {
            
            $this->app->config()->set('middleware.disabled', false);
            
            return $this;
        }
        
        foreach (Arr::wrap($middleware) as $abstract) {
            
            if ( ! $abstract instanceof Middleware) {
                throw new RuntimeException(
                    "You are trying to enable the middleware [$abstract] but it does not implement [Snicco\Contracts\Middleware]."
                );
            }
            
            $this->app->container()
                      ->singleton(get_class($abstract), fn() => $abstract);
            
        }
        
        return $this;
    }
    
    /**
     * Disables exception handling. Exceptions will not be converted to HTTP Responses
     *
     * @return TestCase
     */
    protected function withoutExceptionHandling() :TestCase
    {
        
        $this->config->set('app.exception_handling', false);
        $this->instance(ExceptionHandler::class, new NullExceptionHandler());
        
        return $this;
        
    }
    
    protected function withOutConfig($keys) :TestCase
    {
        
        foreach (Arr::wrap($keys) as $key) {
            $this->config->remove($key);
        }
        
        return $this;
        
    }
    
    protected function addRoute(Route $route)
    {
        
        $this->additional_routes[] = $route;
    }
    
    protected function loadRoutes() :TestCase
    {
        
        if ($this->routes_loaded) {
            return $this;
        }
        
        /** @var AbstractRouteCollection $routes */
        $routes = $this->app->resolve(AbstractRouteCollection::class);
        
        /** @var RouteRegistrar $registrar */
        $registrar = $this->app->resolve(RouteRegistrarInterface::class);
        $registrar->loadApiRoutes($this->config);
        $registrar->loadStandardRoutes($this->config);
        
        foreach ($this->additional_routes as $route) {
            
            $routes->add($route);
            
        }
        
        $registrar->loadIntoRouter();
        
        $this->routes_loaded = true;
        
        return $this;
        
    }
    
    protected function withRequest(Request $request) :TestCase
    {
        $this->request = $request;
        return $this;
    }
    
    // bind a base request for the test so that we don't get possible errors inside CaptureRequest::bootstrap()
    private function bindRequest()
    {
        
        if (isset($this->request)) {
            
            $this->request = $this->addCookies($this->request);
            $this->request = $this->addHeaders($this->request);
            $this->instance(Request::class, $this->request);
            
            return;
        }
        
        $request = new Request(
            $this->request_factory->createServerRequest(
                'GET',
                $this->createUri($this->config->get('app.url')),
                $this->default_server_variables
            )
        );
        $request = $this->addCookies($request);
        $request = $this->addHeaders($request);
        $this->instance(Request::class, $request);
        $this->request = $request;
        
    }
    
    private function setUpTraits()
    {
        
        $traits = array_flip(class_uses_recursive(static::class));
        
        if (in_array(WithDatabaseExceptions::class, $traits)) {
            $this->withDatabaseExceptions();
        }
        
    }
    
    private function setProperties()
    {
        
        if (in_array(SessionServiceProvider::class, $this->config->get('app.providers'))
            && $this->config->get('session.enabled')) {
            
            $this->session = $this->app->resolve(Session::class);
            
        }
        
    }
    
    private function callBeforeApplicationDestroyedCallbacks()
    {
        
        foreach ($this->before_application_destroy_callbacks as $callback) {
            $callback();
            
        }
    }
    
}