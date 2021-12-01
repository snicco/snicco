<?php

namespace Snicco\ExceptionHandling;

use Throwable;
use Snicco\View\ViewEngine;
use Snicco\Http\Psr7\Request;
use Snicco\ExceptionHandling\Exceptions\HttpException;
use Snicco\ExceptionHandling\Exceptions\ErrorViewException;

class HtmlErrorRenderer
{
    
    private ViewEngine $view_engine;
    
    public function __construct(ViewEngine $view_factory)
    {
        $this->view_engine = $view_factory;
    }
    
    /**
     * This function is the last possible way to render an exception, if anything fails here
     * there is nothing we can do.
     *
     * @param  HttpException  $e
     * @param  Request  $request
     *
     * @return string
     * @throws ErrorViewException
     */
    public function render(HttpException $e, Request $request) :string
    {
        $views = $this->getPossibleViews($e, $request);
        
        try {
            return $this->view_engine->render($views, [
                'status_code' => $e->httpStatusCode(),
                'message' => $e->messageForUsers(),
            ]);
        } catch (Throwable $e) {
            throw new ErrorViewException('Critical error while rendering an error view.', $e);
        }
    }
    
    private function getPossibleViews(HttpException $e, Request $request) :array
    {
        $views = ['framework.errors.'.$e->httpStatusCode(), 'framework.errors.500'];
        
        if ( ! $request->isWpAdmin()) {
            return $views;
        }
        
        $admin_views = array_map(function ($view) {
            return "$view-admin";
        }, $views);
        
        return array_merge($admin_views, $views);
    }
    
}