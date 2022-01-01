<?php

declare(strict_types=1);

namespace Snicco\Core\Controllers;

use Snicco\Core\Http\AbstractController;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Contracts\TemplateRenderer;

class ViewAbstractController extends AbstractController
{
    
    /**
     * @var TemplateRenderer
     */
    private $creates_views;
    
    public function __construct(TemplateRenderer $creates_views)
    {
        $this->creates_views = $creates_views;
    }
    
    public function handle(...$args) :Response
    {
        [$view, $data, $status, $headers] = array_slice($args, -4);
        
        $response = $this->respond()->html(
            $this->creates_views->render($view, $data),
            $status
        );
        
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        return $response;
    }
    
}