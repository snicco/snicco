<?php

declare(strict_types=1);

namespace Snicco\ViewBundle;

use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Contracts\CreatesHtmlResponse;

interface ViewResponseFactory extends ResponseFactory, CreatesHtmlResponse
{
    
    public function view(string $view, array $data = [], int $status = 200, array $headers = []) :Response;
    
}