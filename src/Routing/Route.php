<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Closure;
use Snicco\Support\Url;
use Snicco\Http\Psr7\Request;
use Snicco\Support\UrlParser;
use Snicco\Traits\ExportsRoute;
use Snicco\Contracts\Condition;
use Snicco\Traits\HydratesRoute;
use Snicco\Contracts\RouteAction;
use Snicco\Traits\SetsRouteAttributes;
use Snicco\Factories\RouteActionFactory;
use Snicco\Factories\RouteConditionFactory;

class Route
{
    
    use SetsRouteAttributes;
    use ExportsRoute;
    use HydratesRoute;
    
    const ROUTE_WILDCARD = '*';
    const ROUTE_FALLBACK_NAME = 'sniccowp_fallback_route';
    
    private array  $methods;
    private string $url;
    private array  $middleware = [];
    private string $namespace  = '';
    private string $name       = '';
    private array  $regex      = [];
    private array  $defaults   = [];
    
    /** @var Closure|null|string A serialized closure if it's a string */
    private $wp_query_filter = null;
    
    private array $segment_names  = [];
    private bool  $trailing_slash = false;
    
    /** @var string|Closure|array */
    private $action;
    
    /** @var array|ConditionBlueprint[] */
    private array $condition_blueprints = [];
    
    /**
     * @var Condition[]
     */
    private array $instantiated_conditions = [];
    
    private RouteAction $instantiated_action;
    
    private bool $is_fallback = false;
    
    private array $captured_parameters = [];
    
    public function __construct(array $methods, string $url, $action = null)
    {
        $this->methods = $methods;
        $this->url = $this->parseUrl($url);
        $this->action = $action;
    }
    
    public function instantiateConditions(RouteConditionFactory $condition_factory) :Route
    {
        $this->instantiated_conditions = $condition_factory->buildConditions(
            $this->condition_blueprints
        );
        
        return $this;
    }
    
    public function instantiateAction(RouteActionFactory $action_factory) :Route
    {
        $this->instantiated_action = $action_factory->create(
            $this->action,
            $this->namespace,
        );
        
        return $this;
    }
    
    public function run(Request $request)
    {
        return $this->instantiated_action->execute(
            array_merge(
                $this->compiledParameters($request),
                $this->defaults
            )
        );
    }
    
    public function satisfiedBy(Request $request) :bool
    {
        foreach ($this->instantiated_conditions as $condition) {
            if ( ! $condition->isSatisfied($request)) {
                return false;
            }
        }
        return true;
    }
    
    public function setCapturedParameters(array $captured_parameters)
    {
        $this->captured_parameters = $captured_parameters;
    }
    
    public function filterWpQuery() :array
    {
        return call_user_func_array($this->wp_query_filter, $this->captured_parameters);
    }
    
    public function needsTrailingSlash() :bool
    {
        return $this->trailing_slash;
    }
    
    public function wantsToFilterWPQuery() :bool
    {
        return ! is_null($this->wp_query_filter);
    }
    
    public function isFallback() :bool
    {
        return $this->is_fallback;
    }
    
    public function routableByCondition() :bool
    {
        return count($this->condition_blueprints) > 0;
    }
    
    public function routableByUrl() :bool
    {
        return trim($this->url, '/') !== Route::ROUTE_WILDCARD;
    }
    
    public function getMethods() :array
    {
        return $this->methods;
    }
    
    public function getRegex() :array
    {
        return $this->regex;
    }
    
    public function getName() :?string
    {
        return $this->name ?? null;
    }
    
    public function getUrl() :string
    {
        return $this->url;
    }
    
    public function getAction()
    {
        return $this->action;
    }
    
    public function getSegmentNames() :array
    {
        return $this->segment_names;
    }
    
    public function getMiddleware() :array
    {
        return array_merge(
            $this->middleware,
            $this->instantiated_action->getMiddleware()
        );
    }
    
    private function conditionArgs(Request $request) :array
    {
        $args = [];
        
        foreach ($this->instantiated_conditions as $condition) {
            $args = array_merge($args, $condition->getArguments($request));
        }
        
        return $args;
    }
    
    private function parseUrl(string $url) :string
    {
        $url = UrlParser::replaceAdminAliases($url);
        
        $url = Url::addLeading($url);
        
        $this->segment_names = UrlParser::segmentNames($url);
        
        return $url;
    }
    
    private function compiledParameters(Request $request) :array
    {
        return $this->routableByUrl() ? $this->captured_parameters : $this->conditionArgs($request);
    }
    
}