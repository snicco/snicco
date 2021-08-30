<?php

namespace Snicco\ExceptionHandling;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\ViewFactoryInterface;
use Snicco\ExceptionHandling\Exceptions\HttpException;
use Snicco\ExceptionHandling\Exceptions\ErrorViewException;

class HtmlErrorRenderer
{
    
    /**
     * This function is the last possible way to render an exception, if anything fails here
     * there is nothing we can do.
     *
     * @param  ViewFactoryInterface  $view_factory
     * @param  HttpException  $e
     * @param  Request  $request
     *
     * @return string|void
     * @throws ErrorViewException
     */
    public function render(ViewFactoryInterface $view_factory, HttpException $e, Request $request)
    {
        
        $views = $this->getPossibleViews($e, $request);
        
        try {
            
            return $view_factory->render($views, [
                'status_code' => $e->httpStatusCode(),
                'message' => $e->messageForUsers(),
            ]);
            
        } catch (\Throwable $e) {
            
            throw new ErrorViewException('Critical error while rendering an error view.', $e);
            
        }
        
    }
    
    private function getPossibleViews(HttpException $e, Request $request) :array
    {
        
        $views = [(string) $e->httpStatusCode(), 'error', 'index'];
        
        if ( ! $request->isWpAdmin()) {
            
            return $views;
            
        }
        
        return collect($views)
            ->map(fn($view) => "$view-admin")
            ->merge($views)->all();
        
    }
    
}