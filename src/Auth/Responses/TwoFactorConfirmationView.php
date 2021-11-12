<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\ViewInterface;
use Snicco\Contracts\ViewFactoryInterface;
use Snicco\Auth\Contracts\Abstract2FAuthConfirmationView;

class TwoFactorConfirmationView extends Abstract2FAuthConfirmationView
{
    
    private ViewFactoryInterface $view_factory;
    
    public function __construct(ViewFactoryInterface $view_factory)
    {
        $this->view_factory = $view_factory;
    }
    
    public function toView(Request $request) :ViewInterface
    {
        return $this->view_factory->make('framework.auth.two-factor-challenge')->with([
            'post_to' => $request->path(),
        ]);
    }
    
}