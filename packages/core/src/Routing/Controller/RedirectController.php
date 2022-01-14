<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Controller;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\AbstractController;
use Snicco\Core\Http\Responses\RedirectResponse;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

/**
 * @interal
 */
final class RedirectController extends AbstractController
{
    
    public function to(...$args) :RedirectResponse
    {
        [$location, $status_code, $query] = array_slice($args, -3);
        
        return $this->redirect()->to($location, $status_code, $query);
    }
    
    public function away(...$args) :RedirectResponse
    {
        [$location, $status_code] = array_slice($args, -2);
        
        return $this->redirect()->away($location, $status_code);
    }
    
    public function toRoute(...$args) :RedirectResponse
    {
        [$route, $arguments, $status_code] = array_slice($args, -3);
        
        return $this->redirect()->toRoute($route, $arguments, $status_code);
    }
    
    /** @todo tests */
    public function exit(Request $request)
    {
        $home_url = '/';
        try {
            $home_url = $this->url()->toRoute('home');
        } catch (RouteNotFound $e) {
            //
        }
        
        $this->render('framework.redirect-protection', [
            'untrusted_url' => $request->query('intended_redirect'),
            'home_url' => $home_url,
        ]);
    }
    
}