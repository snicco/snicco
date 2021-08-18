<?php

declare(strict_types=1);

return [
    
    'paths' => [
        
        VIEWS_DIR,
        VIEWS_DIR.DS.'subdirectory',
        BLADE_VIEWS,
        BLADE_VIEWS.DS.'blade-features',
        BLADE_VIEWS.DS.'layouts',
    
    ],
    
    'composers' => [
        
        'Tests\fixtures\ViewComposers',
    
    ],
    
    'blade_cache' => BLADE_CACHE,

];