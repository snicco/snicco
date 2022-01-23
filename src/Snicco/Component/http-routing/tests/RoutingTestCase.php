<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests;

use PHPUnit\Framework\TestCase;
use Snicco\Testing\TestResponse;
use Psr\Container\ContainerInterface;
use Snicco\Component\Core\DIContainer;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Component\Core\Utils\PHPCacheFile;
use Snicco\Component\HttpRouting\Routing\Router;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Http\HttpKernel;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\ResponseFactory;
use Snicco\Component\HttpRouting\Middleware\ShareCookies;
use Snicco\Component\EventDispatcher\BaseEventDispatcher;
use Snicco\Component\HttpRouting\Http\ResponsePreparation;
use Snicco\Component\HttpRouting\Http\FileTemplateRenderer;
use Snicco\Component\HttpRouting\Middleware\MethodOverride;
use Snicco\Component\HttpRouting\Middleware\MustMatchRoute;
use Snicco\Component\HttpRouting\Testing\CreatesPsrRequests;
use Snicco\Component\Core\ExceptionHandling\ExceptionHandler;
use Snicco\Component\EventDispatcher\TestableEventDispatcher;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\BazMiddleware;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;
use Snicco\Component\HttpRouting\Middleware\Internal\RouteRunner;
use Snicco\Component\HttpRouting\Tests\fixtures\FoobarMiddleware;
use Snicco\Component\Core\ExceptionHandling\NullExceptionHandler;
use Snicco\Component\HttpRouting\Routing\AdminDashboard\AdminArea;
use Snicco\Component\HttpRouting\Routing\Controller\ViewController;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\AdminDashboard\WPAdminArea;
use Snicco\Component\HttpRouting\Middleware\Internal\MiddlewareStack;
use Snicco\Component\HttpRouting\Middleware\Internal\PrepareResponse;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\RFC3986Encoder;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsrContainer;
use Snicco\Component\HttpRouting\Middleware\Internal\MiddlewareFactory;
use Snicco\Component\HttpRouting\Middleware\Internal\RoutingMiddleware;
use Snicco\Component\HttpRouting\Routing\Controller\FallBackController;
use Snicco\Component\HttpRouting\Routing\Controller\RedirectController;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\HttpRouting\Middleware\Internal\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Routing\Condition\RouteConditionFactory;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorFactory;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfiguratorUsingRouter;

/**
 * @interal
 */
class RoutingTestCase extends TestCase
{
    
    use CreateTestPsr17Factories;
    use CreatesPsrRequests;
    use CreateTestPsrContainer;
    
    const CONTROLLER_NAMESPACE = 'Snicco\\Component\\HttpRouting\\Tests\\fixtures\\Controller';
    
    protected string                  $app_domain = 'foobar.com';
    protected string                  $routes_dir;
    protected ResponseFactory         $response_factory;
    protected ContainerInterface      $container;
    protected TestableEventDispatcher $event_dispatcher;
    protected UrlGenerator            $generator;
    
    private Router                 $router;
    private HttpKernel             $kernel;
    private AdminArea              $admin_area;
    private UrlGenerationContext   $request_context;
    private MiddlewareStack        $middleware_stack;
    private WebRoutingConfigurator $routing_configurator;
    private MiddlewarePipeline     $pipeline;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->createNeededCollaborators();
        $this->container[RoutingTestController::class] = new RoutingTestController();
        $this->routes_dir = __DIR__.'/fixtures/routes';
    }
    
    final protected function assertEmptyBody(Request $request)
    {
        $this->assertResponseBody('', $request);
    }
    
    final protected function assertResponseBody($expected, Request $request)
    {
        $response = $this->runKernel($request);
        $this->assertSame(
            $expected,
            $b = $response->body(),
            "Expected response body [$expected] for path [{$request->path()}].\nGot [$b]."
        );
    }
    
    /**
     * @param  array<string,array<string>>  $middlewares
     */
    final protected function withMiddlewareGroups(array $middlewares) :void
    {
        foreach ($middlewares as $name => $middleware) {
            $this->middleware_stack->withMiddlewareGroup($name, $middleware);
        }
    }
    
    final protected function withGlobalMiddleware(array $middleware)
    {
        $this->withMiddlewareGroups([RoutingConfigurator::GLOBAL_MIDDLEWARE => $middleware]);
    }
    
    final protected function withNewMiddlewareStack(MiddlewareStack $middleware_stack)
    {
        $this->middleware_stack = $middleware_stack;
        $this->container[MiddlewareStack::class] = $middleware_stack;
        $this->container[RouteRunner::class] = new RouteRunner(
            $this->pipeline,
            $middleware_stack,
            $this->container
        );
    }
    
    final protected function withMiddlewareAlias(array $aliases)
    {
        $this->middleware_stack->middlewareAliases($aliases);
    }
    
    final protected function withMiddlewarePriority(array $array)
    {
        $this->middleware_stack->middlewarePriority($array);
    }
    
    final protected function runKernel(Request $request) :TestResponse
    {
        $this->withMiddlewareAlias($this->defaultMiddlewareAliases());
        
        $response = $this->kernel->handle($request);
        return new TestResponse($response);
    }
    
    final protected function routeConfigurator() :WebRoutingConfigurator
    {
        return $this->routing_configurator;
    }
    
    final protected function adminRouteConfigurator() :AdminRoutingConfigurator
    {
        return $this->routing_configurator;
    }
    
    final protected function refreshRouter(PHPCacheFile $cache_file = null, UrlGenerationContext $context = null, array $config = [])
    {
        unset($this->container[RoutingMiddleware::class]);
        unset($this->container[UrlGenerator::class]);
        unset($this->container[UrlMatcher::class]);
        
        if (is_null($context)) {
            $context = $this->request_context ?? UrlGenerationContext::forConsole(
                    $this->app_domain,
                );
        }
        
        $this->request_context = $context;
        
        $this->admin_area ??= WPAdminArea::fromDefaults();
        
        $this->router = new Router(
            $this->container[RouteConditionFactory::class],
            new UrlGeneratorFactory(
                $context,
                $this->admin_area,
                new RFC3986Encoder(),
            ),
            $this->admin_area,
            $cache_file
        );
        
        $this->routing_configurator = new RoutingConfiguratorUsingRouter(
            $this->router,
            $this->admin_area->urlPrefix(),
            $config
        );
        
        $this->container->instance(UrlGenerator::class, $this->router);
        $this->container->instance(RoutingMiddleware::class, new RoutingMiddleware($this->router));
        $this->container->instance(UrlMatcher::class, $this->router);
        $this->generator = $this->router;
    }
    
    final protected function refreshUrlGenerator(UrlGenerationContext $context = null) :UrlGenerator
    {
        $this->refreshRouter(null, $context);
        return $this->generator;
    }
    
    protected function baseUrl() :string
    {
        return 'https://'.$this->app_domain;
    }
    
    protected function adminArea() :AdminArea
    {
        return $this->admin_area;
    }
    
    protected function urlGenerator() :UrlGenerator
    {
        return $this->generator;
    }
    
    final private function defaultMiddlewareAliases() :array
    {
        return [
            'foo' => FooMiddleware::class,
            'bar' => BarMiddleware::class,
            'baz' => BazMiddleware::class,
            'foobar' => FoobarMiddleware::class,
        ];
    }
    
    /**
     * Create instances that are necessary for running routes.
     */
    final private function createNeededCollaborators()
    {
        $this->container = $this->createContainer();
        $this->container->instance(DIContainer::class, $this->container);
        
        $this->admin_area = WPAdminArea::fromDefaults();
        $this->container[AdminArea::class] = $this->admin_area;
        
        $condition_factory = new RouteConditionFactory($this->container);
        $this->container[RouteConditionFactory::class] = $condition_factory;
        
        $this->refreshRouter();
        
        $this->response_factory = $this->createResponseFactory($this->generator);
        $this->container->instance(ResponseFactory::class, $this->response_factory);
        $this->container->instance(Redirector::class, $this->response_factory);
        $this->container->instance(StreamFactoryInterface::class, $this->response_factory);
        
        $error_handler = new NullExceptionHandler();
        $this->container->instance(ExceptionHandler::class, $error_handler);
        
        $this->kernel = new HttpKernel(
            $this->pipeline = new MiddlewarePipeline(
                $middleware_factory = new MiddlewareFactory($this->container),
                $error_handler,
            ),
            $this->event_dispatcher = new TestableEventDispatcher(
                new BaseEventDispatcher()
            )
        );
        
        // Middleware
        $this->middleware_stack = new MiddlewareStack();
        $this->container->instance(MiddlewareStack::class, $this->middleware_stack);
        
        $this->container->instance(
            PrepareResponse::class,
            new PrepareResponse(new ResponsePreparation($this->psrStreamFactory()))
        );
        
        $this->container->instance(MethodOverride::class, new MethodOverride());
        
        $this->container->singleton(RoutingMiddleware::class, function () {
            return new RoutingMiddleware(
                $this->router,
            );
        });
        
        $this->container->instance(
            RouteRunner::class,
            new RouteRunner(
                new MiddlewarePipeline(
                    $middleware_factory,
                    $error_handler,
                ),
                $this->middleware_stack,
                $this->container,
            )
        );
        
        $this->container->instance(
            MustMatchRoute::class,
            new MustMatchRoute()
        );
        $this->container->instance(ShareCookies::class, new ShareCookies());
        
        // internal controllers
        $this->container->instance(FallBackController::class, new FallBackController());
        
        $this->container->instance(
            ViewController::class,
            new ViewController(new FileTemplateRenderer())
        );
        
        $this->container->instance(RedirectController::class, new RedirectController());
    }
    
}