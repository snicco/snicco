<?php

declare(strict_types=1);

namespace Snicco\Traits;

trait ValidatesWordpressNonces
{
    
    public function hasValidAjaxNonce($nonce_action, $query_arg = null) :bool
    {
        $nonce = '';
        
        if ($query_arg && $this->has($query_arg)) {
            $nonce = $this->input($query_arg, '');
        }
        elseif ($this->has('_ajax_nonce')) {
            $nonce = $this->input('_ajax_nonce', '');
        }
        elseif ($this->has('_wpnonce')) {
            $nonce = $this->input('_wpnonce');
        }
        
        return wp_verify_nonce($nonce, $nonce_action) !== false;
    }
    
}