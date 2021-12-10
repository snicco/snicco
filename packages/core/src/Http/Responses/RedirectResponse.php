<?php

declare(strict_types=1);

namespace Snicco\Core\Http\Responses;

use Snicco\Core\Http\Psr7\Response;

final class RedirectResponse extends Response
{
    
    /**
     * @var bool
     */
    private $bypass_validation = false;
    
    /**
     * @api
     */
    public function to(string $url) :Response
    {
        return $this->withHeader('Location', $url);
    }
    
    /**
     * @interal
     */
    public function canBypassValidation() :bool
    {
        return $this->bypass_validation;
    }
    
    /**
     * @interal
     */
    public function bypassValidation() :RedirectResponse
    {
        $this->bypass_validation = true;
        return $this;
    }
    
}
