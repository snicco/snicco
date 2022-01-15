<?php

declare(strict_types=1);

namespace Snicco\Auth\Confirmation;

use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\Auth\Contracts\AuthConfirmation;
use Snicco\Component\Core\Contracts\MagicLink;
use Snicco\Auth\Contracts\AbstractEmailAuthConfirmationView;
use Snicco\HttpRouting\Routing\UrlGenerator\InternalUrlGenerator;

class EmailAuthConfirmation implements AuthConfirmation
{
    
    private MagicLink                         $magic_link;
    private AbstractEmailAuthConfirmationView $response;
    private InternalUrlGenerator              $url;
    
    public function __construct(MagicLink $magic_link, AbstractEmailAuthConfirmationView $response, InternalUrlGenerator $url)
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
    
    public function view(Request $request)
    {
        return $this->response->toView($request)->with(
            'send_email_route',
            $this->url->toRoute('auth.confirm.email')
        )->toString();
    }
    
}