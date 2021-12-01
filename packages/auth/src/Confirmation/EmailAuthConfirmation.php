<?php

declare(strict_types=1);

namespace Snicco\Auth\Confirmation;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\MagicLink;
use Snicco\Routing\UrlGenerator;
use Snicco\View\Contracts\ViewInterface;
use Snicco\Auth\Contracts\AuthConfirmation;
use Snicco\Auth\Contracts\AbstractEmailAuthConfirmationView;

class EmailAuthConfirmation implements AuthConfirmation
{
    
    private MagicLink                         $magic_link;
    private AbstractEmailAuthConfirmationView $response;
    private UrlGenerator                      $url;
    
    public function __construct(MagicLink $magic_link, AbstractEmailAuthConfirmationView $response, UrlGenerator $url)
    {
        $this->magic_link = $magic_link;
        $this->response = $response;
        $this->url = $url;
    }
    
    public function confirm(Request $request) :bool
    {
        $valid = $this->magic_link->hasValidSignature($request, true);
        
        if ( ! $valid) {
            return false;
        }
        
        $this->magic_link->invalidate($request->fullUrl());
        
        return true;
    }
    
    public function viewResponse(Request $request) :ViewInterface
    {
        return $this->response->toView($request)->with(
            'send_email_route',
            $this->url->toRoute('auth.confirm.email')
        );
    }
    
}