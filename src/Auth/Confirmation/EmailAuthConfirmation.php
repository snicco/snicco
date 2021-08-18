<?php

declare(strict_types=1);

namespace Snicco\Auth\Confirmation;

use Snicco\View\ViewFactory;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\MagicLink;
use Snicco\Routing\UrlGenerator;
use Snicco\Auth\Contracts\AuthConfirmation;

class EmailAuthConfirmation implements AuthConfirmation
{
    
    private ViewFactory  $view_factory;
    private MagicLink    $magic_link;
    private UrlGenerator $url;
    
    public function __construct(MagicLink $magic_link, ViewFactory $view_factory, UrlGenerator $url)
    {
        $this->magic_link = $magic_link;
        $this->view_factory = $view_factory;
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
    
    public function viewResponse(Request $request)
    {
        
        return $this->view_factory->make('auth-layout')
                                  ->with([
                                      'view' => 'auth-confirm-via-email',
                                      'post_to' => $this->url->toRoute('auth.confirm.email'),
                                  ]);
        
    }
    
}