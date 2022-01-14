<?php

declare(strict_types=1);

namespace Snicco\Core\EventDispatcher\Events;

use Snicco\StrArr\Str;

use function rest_get_url_prefix;

class IncomingWebRequest extends IncomingRequest
{
    
    public function shouldDispatch() :bool
    {
        return $this->request->isToFrontend()
               && ! Str::contains(
                $this->request->path(),
                rest_get_url_prefix()
            );
    }
    
}