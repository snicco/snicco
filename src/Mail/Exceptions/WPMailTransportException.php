<?php

declare(strict_types=1);

namespace Snicco\Mail\Exceptions;

use WP_Error;
use RuntimeException;
use Snicco\Mail\Contracts\TransportException;

final class WPMailTransportException extends RuntimeException implements TransportException
{
    
    private string $debug_data;
    
    public function __construct($message = "", string $debug_data = '')
    {
        parent::__construct($message,);
        $this->debug_data = $debug_data;
    }
    
    public static function becauseWPMailRaisedErrors(WP_Error $error) :WPMailTransportException
    {
        $errors = implode("\n", $error->get_error_messages());
        
        return new static("wp_mail() failure. Errors: $errors");
    }
    
    public function getDebugData() :string
    {
        return $this->debug_data;
    }
    
}