<?php

declare(strict_types=1);

namespace Snicco\Auth\Listeners;

use Snicco\Support\WP;
use Snicco\Routing\UrlGenerator;
use Snicco\Auth\Events\GenerateLoginUrl;
use Snicco\Auth\Events\GenerateLogoutUrl;

class WpLoginLinkGenerator
{
    
    /** NOTE: WordPress always returns these as absolute urls so lets stay compatible */
    public function loginUrl(GenerateLoginUrl $event, UrlGenerator $url) :string
    {
        $query = [];
        
        $query['redirect_to'] = $event->redirect_to !== ''
            ? $event->redirect_to
            : $url->toRoute('dashboard');
        
        if ($event->force_reauth) {
            $query['reauth'] = 'yes';
        }
        
        return $url->toRoute('auth.login', [
            'query' => $query,
        ], true, true);
    }
    
    /** NOTE: WordPress always returns these as absolute urls so lets stay compatible */
    public function logoutUrl(GenerateLogoutUrl $event, UrlGenerator $url) :string
    {
        $redirect = $event->redirect_to;
        
        $url = $url->signedLogout(WP::userId(), $redirect, 3600, true);
        
        return esc_html($url);
    }
    
}