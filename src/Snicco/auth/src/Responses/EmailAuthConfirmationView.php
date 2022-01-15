<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\View\ViewEngine;
use Snicco\View\Contracts\ViewInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Auth\Contracts\AbstractEmailAuthConfirmationView;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\InternalUrlGenerator;

class EmailAuthConfirmationView extends AbstractEmailAuthConfirmationView
{
    
    private ViewEngine           $view_engine;
    private InternalUrlGenerator $url;
    
    public function __construct(ViewEngine $view_engine, InternalUrlGenerator $url)
    {
        $this->view_engine = $view_engine;
        $this->url = $url;
    }
    
    public function toView(Request $request) :ViewInterface
    {
        return $this->view_engine->make('framework.auth.confirm-with-email')
                                 ->with('post_to', $this->url->toRoute('auth.confirm.email'));
    }
    
}