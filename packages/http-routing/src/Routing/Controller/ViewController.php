<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Routing\Controller;

use Snicco\HttpRouting\Http\Psr7\Response;
use Snicco\HttpRouting\Http\TemplateRenderer;
use Snicco\HttpRouting\Http\AbstractController;

/**
 * @interal
 */
final class ViewController extends AbstractController
{
    
    private TemplateRenderer $creates_views;
    
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