<?php

namespace Snicco\Component\HttpRouting\Testing;

use Snicco\Component\HttpRouting\Routing\AdminDashboard\AdminArea;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

use function array_merge;

/**
 * @api
 */
trait CreatesUrls
{
    
    abstract protected function adminArea() :AdminArea;
    
    abstract protected function urlGenerator() :UrlGenerator;
    
    final protected function adminUrl(string $path, array $query_args = []) :string
    {
        $path = $this->adminArea()->urlPrefix()->appendPath($path);
        $arr = $this->adminArea()->rewriteForUrlGeneration($path);
        
        return $this->urlGenerator()->to(
            $arr[0],
            array_merge($query_args, $arr[1]),
            UrlGenerator::ABSOLUTE_URL
        );
    }
    
    final protected function frontendUrl(string $path, array $query_args = []) :string
    {
        return $this->urlGenerator()->to(
            $path,
            $query_args,
            UrlGenerator::ABSOLUTE_URL
        );
    }
    
}