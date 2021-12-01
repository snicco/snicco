<?php

declare(strict_types=1);

use Tests\Core\fixtures\TestDoubles\UserServiceProvider;

return [
    
    'key' => TEST_APP_KEY,
    'foo' => 'bar',
    'bar' => ['baz' => 'boo'],
    
    'providers' => [
        
        UserServiceProvider::class,
    
    ],

];