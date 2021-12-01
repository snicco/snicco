<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Events;

use Snicco\EventDispatcher\Contracts\MappedFilter;

class PreWP404 extends CoreEvent implements MappedFilter
{
    
    public bool $process_404 = true;
    
    public function filterableAttribute() :bool
    {
        // Returned false means WordPress will process 404s. Great API.
        // see apply_filters( 'pre_handle_404', false, $wp_query )
        return $this->process_404;
    }
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}