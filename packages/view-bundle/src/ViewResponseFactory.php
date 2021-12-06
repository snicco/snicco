<?php

declare(strict_types=1);

namespace Snicco\ViewBundle;

use Snicco\Http\Psr7\Response;
use Snicco\Contracts\ResponseFactory;
use Snicco\Contracts\CreatesHtmlResponse;

interface ViewResponseFactory extends ResponseFactory, CreatesHtmlResponse
{
    
    public function view(string $view, array $data = [], int $status = 200, array $headers = []) :Response;
    
}