<?php

declare(strict_types=1);

/**
 * This array will be passed as into {@see \Snicco\Session\ValueObjects\SessionConfig::__construct}
 */
return [
    
    'cookie_name' => 'snicco_test_session',
    
    'idle_timeout_in_sec' => 60 * 15,
    
    // Null => expires on browser close
    'absolute_lifetime_in_sec' => null,
    
    'rotation_interval_in_sec' => 3600,
    
    'garbage_collection_percentage' => 2,
    
    'driver' => 'array',
    
    'rotate' => 3600,
    
    'lifetime' => 7200,

];
