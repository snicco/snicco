<?php

namespace Snicco\ExceptionHandling;

use Throwable;
use Snicco\Http\Psr7\Request;
use Snicco\View\Contracts\ViewFactoryInterface;
use Snicco\ExceptionHandling\Exceptions\HttpException;
use Snicco\ExceptionHandling\Exceptions\ErrorViewException;

class HtmlErrorRenderer
{
    
    private ViewFactoryInterface $view_factory;
    
    public function __construct(ViewFactoryInterface $view_factory)
    {
        $this->view_factory = $view_factory;
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
            return $this->view_factory->render($views, [
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
        
        return collect($views)
            ->map(fn($view) => "$view-admin")
            ->merge($views)->all();
    }
    
}