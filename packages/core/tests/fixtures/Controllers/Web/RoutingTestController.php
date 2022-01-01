<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Controllers\Web;

use Snicco\Core\Http\Psr7\Request;
use Tests\Codeception\shared\TestDependencies\Foo;

class RoutingTestController
{
    
    const static = 'static';
    const dynamic = 'dynamic';
    
    public function __invoke()
    {
        return $this->static();
    }
    
    public function static() :string
    {
        return 'static';
    }
    
    public function dynamic(string $param) :string
    {
        return 'dynamic:'.$param;
    }
    
    public function dynamicInt(int $param) :string
    {
        return 'dynamic:'.$param;
    }
    
    public function twoParams(string $param1, string $param2) :string
    {
        return $param1.':'.$param2;
    }
    
    public function twoParamsWithRequest(Request $request, string $param1, string $param2) :string
    {
        return $param1.':'.$param2;
    }
    
    public function twoParamsWithDependency(Foo $foo, string $param1, string $param2) :string
    {
        return $foo->foo.':'.$param1.':'.$param2;
    }
    
    public function twoParamsWithDependencyAndRequest(Request $request, Foo $foo, string $param1, string $param2) :string
    {
        return $foo->foo.':'.$param1.':'.$param2;
    }
    
    public function requestDependencyParamsCondition(Request $request, Foo $foo, string $param1, string $param2, string $condition_arg) :string
    {
        return $foo->foo.':'.$param1.':'.$param2.':'.$condition_arg;
    }
    
    public function twoOptional($param1 = 'default1', string $param2 = 'default2') :string
    {
        return "$param1:$param2";
    }
    
    public function requiredAndOptional(string $param1, string $param2 = 'default1', string $param3 = 'default2') :string
    {
        return "$param1:$param2:$param3";
    }
    
    public function users(int $id, string $name = 'default_user') :string
    {
        return 'dynamic:'.$id.':'.$name;
    }
    
    public function bandSong(string $band, string $song = null) :string
    {
        if ($song) {
            return "Show song [$song] of band [$band].";
        }
        
        return "Show all songs of band [$band].";
    }
    
    public function onlyRequest(Request $request)
    {
        return $this->static();
    }
    
    public function fallback(string $path)
    {
        return 'fallback:'.$path;
    }
    
}