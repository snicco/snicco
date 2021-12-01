<?php

declare(strict_types=1);

namespace Snicco\Testing;

use Mockery;
use RuntimeException;
use Snicco\Support\WP;
use Snicco\Support\Arr;
use Snicco\Support\Str;
use Snicco\Http\Delegate;
use Snicco\Http\HttpKernel;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;
use Snicco\Application\Config;
use Snicco\Contracts\Middleware;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Application\Application;
use Illuminate\Container\Container;
use Codeception\TestCase\WPTestCase;
use Snicco\Contracts\ServiceProvider;
use Snicco\Contracts\ExceptionHandler;
use Illuminate\Support\Facades\Facade;
use Psr\Http\Message\ResponseInterface;
use Illuminate\Database\Eloquent\Model;
use Snicco\Testing\Concerns\TravelsTime;
use Snicco\Session\SessionServiceProvider;
use Mockery\Exception\InvalidCountException;
use Snicco\Testing\Concerns\InteractsWithMail;
use Snicco\Testing\Concerns\MakesHttpRequests;
use Snicco\Testing\Concerns\InteractsWithEvents;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Testing\Concerns\InteractsWithSession;
use Snicco\ExceptionHandling\NullExceptionHandler;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Snicco\Testing\Concerns\InteractsWithContainer;
use Snicco\Testing\Concerns\InteractsWithAuthentication;
use Snicco\Testing\Concerns\InteractsWithWordpressUsers;

use function Snicco\Support\Functions\classUsesRecursive;

abstract class TestCase extends WPTestCase
{
    
    use MakesHttpRequests;
    use InteractsWithEvents;
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
    protected bool                          $routes_loaded = false;
    
    /** @var callable[] */
    protected array $after_application_booted = [];
    
    /**
     * @var callable[]
     */
    private array $after_application_created = [];
    
    /**
     * @var callable[]
     */
    private array $before_application_destroyed = [];
    
    protected function setUp() :void
    {
        parent::setUp();
        
        if (class_exists(Facade::class)) {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);
        }
        
        if (class_exists(Container::class)) {
            Container::setInstance();
        }
        
        if (class_exists(Model::class)) {
            Model::clearBootedModels();
            Model::unsetConnectionResolver();
            Model::unsetEventDispatcher();
        }
        
        WP::reset();
        
        $this->backToPresent();
        
        if ( ! isset($this->app)) {
            $this->refreshApplication();
        }
        
        $this->mergeServiceProviders();
        $this->bindBaseRequest();
        $this->setUpTraits();
        
        $this->afterApplicationBooted(function () {
            $this->replaceBindings();
            $this->setProperties();
            $this->dispatcher = $this->app[Dispatcher::class];
            $this->fake_mailer = $this->app[Mailer::class];
        });
        
        foreach ($this->after_application_created as $callback) {
            $callback();
        }
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        
        if (isset($this->app)) {
            foreach ($this->before_application_destroyed as $callback) {
                $callback();
            }
            
            unset($this->app);
        }
        
        if (class_exists(Mockery::class)) {
            if ($container = Mockery::getContainer()) {
                $this->addToAssertionCount($container->mockery_getExpectationCount());
            }
            
            try {
                Mockery::close();
            } catch (InvalidCountException $e) {
                if ( ! Str::contains((string) $e->getMethodName(), ['doWrite', 'askQuestion'])) {
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
        
        if (class_exists(Model::class)) {
            Model::clearBootedModels();
            Model::unsetConnectionResolver();
            Model::unsetEventDispatcher();
        }
        
        $this->backToPresent();
        
        // WpTestCase will take care of resetting the user.
        //$this->logout();
        WP::reset();
    }
    
    abstract protected function createApplication() :Application;
    
    protected function baseUrl() :string
    {
        return $this->app->config('app.url');
    }
    
    /**
     * @return ServiceProvider[]
     */
    protected function packageProviders() :array
    {
        return [];
    }
    
    /**
     * Register callbacks that will be run after the application has been initialized.
     * The config will be loaded at this point but ServiceProviders are not booted yet.
     *
     * @param  callable  $callback
     */
    protected function afterApplicationCreated(callable $callback)
    {
        if (isset($this->app) && $this->app->hasBeenBootstrapped()) {
            $this->fail('Application had already been bootstrapped before adding callable.');
        }
        $this->after_application_created[] = $callback;
    }
    
    protected function beforeApplicationDestroyed(callable $callback)
    {
        $this->before_application_destroyed[] = $callback;
    }
    
    /**
     * Register callbacks that will be run after the application has fully been bootstrapped.
     * All ServiceProviders are already registered and booted at this point.
     *
     * @param  callable  $callback
     */
    protected function afterApplicationBooted(callable $callback)
    {
        if (isset($this->app) && $this->app->hasBeenBootstrapped()) {
            $this->fail('Application had already been bootstrapped before adding callable.');
        }
        
        $this->after_application_booted[] = $callback;
    }
    
    protected function refreshApplication()
    {
        $this->app = $this->createApplication();
        $this->config = $this->app->config();
    }
    
    protected function bootApp() :self
    {
        if ($this->app->hasBeenBootstrapped()) {
            $this->fail('Application bootstrapped twice in one test.');
        }
        
        $this->app->boot();
        
        foreach ($this->after_application_booted as $callback) {
            $callback();
        }
        
        return $this;
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
                    $this->fail(
                        "You are trying to disable the middleware [$abstract] which does not seem to exist."
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
    
    protected function withRequest(Request $request) :TestCase
    {
        $this->request = $this->addRequestDefaults($request);
        return $this;
    }
    
    private function mergeServiceProviders() :void
    {
        $this->config->extend('app.providers', $this->packageProviders());
    }
    
    // bind a base request for the test so that we don't get possible errors inside CaptureRequest::bootstrap()
    private function bindBaseRequest()
    {
        if ( ! isset($this->request_factory)) {
            $this->request_factory = $this->app->resolve(ServerRequestFactoryInterface::class);
        }
        
        if (isset($this->request)) {
            $this->addRequestDefaults($this->request);
            
            return;
        }
        
        $url = $_ENV['APP_URL'] ?? ($_SERVER['WP_SITE_URL'] ?? 'https://localhost.test');
        $this->config->set('app.url', $url);
        
        $request = new Request(
            $this->request_factory->createServerRequest(
                'GET',
                $this->createUri($url),
                $this->default_server_variables
            )
        );
        
        $this->addRequestDefaults($request);
        $this->request = $request;
    }
    
    private function setUpTraits()
    {
        $traits = array_flip(classUsesRecursive(static::class));
        
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
    
}