<?php

declare(strict_types=1);

namespace Snicco\Auth\Listeners;

use Snicco\Core\Support\WP;
use Snicco\Auth\Events\GenerateLoginUrl;
use Snicco\Auth\Events\GenerateLogoutUrl;
use Snicco\Core\Routing\Internal\Generator;

class WPLoginLinks
{
    
    private Generator $url_generator;
    
    public function __construct(Generator $url_generator)
    {
        $this->url_generator = $url_generator;
    }
    
    /** NOTE: WordPress always returns these as absolute urls so lets stay compatible */
    public function createLoginUrl(GenerateLoginUrl $event)
    {
        $query = [];
        
        $query['redirect_to'] = $event->redirect_to !== ''
            ? $event->redirect_to
            : $this->url_generator->toRoute('dashboard');
        
        if ($event->force_reauth) {
            $query['reauth'] = 'yes';
        }
        
        $event->url = $this->url_generator->toRoute('auth.login', [
            'query' => $query,
        ], true, true);
    }
    
    /** NOTE: WordPress always returns these as absolute urls so lets stay compatible */
    public function createLogoutUrl(GenerateLogoutUrl $event)
    {
        $redirect = $event->redirect_to;
        
        $url = $this->url_generator->signedLogout(WP::userId(), $redirect, 3600, true);
        
        $event->url = esc_html($url);
    }
    
}