<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Http\Psr7\Request;
use Snicco\Routing\UrlGenerator;
use Snicco\View\Contracts\ViewInterface;
use Snicco\View\Contracts\ViewFactoryInterface;
use Snicco\Auth\Contracts\AbstractEmailAuthConfirmationView;

class EmailAuthConfirmationView extends AbstractEmailAuthConfirmationView
{
    
    private ViewFactoryInterface $view_factory;
    private UrlGenerator         $url;
    
    public function __construct(ViewFactoryInterface $view_factory, UrlGenerator $url)
    {
        $this->view_factory = $view_factory;
        $this->url = $url;
    }
    
    public function toView(Request $request) :ViewInterface
    {
        return $this->view_factory->make('framework.auth.confirm-with-email')
                                  ->with('post_to', $this->url->toRoute('auth.confirm.email'));
    }
    
}