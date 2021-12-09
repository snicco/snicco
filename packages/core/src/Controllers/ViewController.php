<?php

declare(strict_types=1);

namespace Snicco\Core\Controllers;

use Snicco\Core\Http\Controller;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Contracts\CreatesHtmlResponse;

class ViewController extends Controller
{
    
    /**
     * @var CreatesHtmlResponse
     */
    private $creates_views;
    
    public function __construct(CreatesHtmlResponse $creates_views)
    {
        $this->creates_views = $creates_views;
    }
    
    public function handle(...$args) :Response
    {
        [$view, $data, $status, $headers] = array_slice($args, -4);
        
        $response = $this->response_factory->html(
            $this->creates_views->getHtml($view, $data),
            $status
        );
        
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        return $response;
    }
    
}