<?php

declare(strict_types=1);

namespace Tests\fixtures\Controllers\Web;

use Snicco\Http\Psr7\Request;
use Tests\fixtures\TestDependencies\Bar;
use Tests\fixtures\TestDependencies\Foo;

class TeamsController
{
    
    public function handle(Request $request, string $team, string $player)
    {
        
        return $team.':'.$player;
        
    }
    
    public function withDependencies(Request $request, string $team, string $player, Foo $foo, Bar $bar)
    {
        
        return $team.':'.$player.':'.$foo->foo.':'.$bar->bar;
        
    }
    
    public function withConditions(Request $request, $team, $player, $baz, $biz, Foo $foo, Bar $bar)
    {
        
        return $team.':'.$player.':'.$baz.':'.$biz.':'.$foo->foo.':'.$bar->bar;
        
    }
    
}