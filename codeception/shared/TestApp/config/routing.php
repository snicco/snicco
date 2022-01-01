<?php

declare(strict_types=1);

use Tests\Core\fixtures\Conditions\TrueRouteCondition;

return [
    
    'definitions' => dirname(__DIR__).'/routes',
    
    'api' => [
        'endpoints' => [
            'test' => 'api-prefix/base',
        ],
    ],
    
    'trailing_slash' => false,
    
    'conditions' => [
        'true' => TrueRouteCondition::class,
    ],

];
