<?php

declare(strict_types=1);

namespace Snicco\Mail\Exceptions;

use WP_Error;
use Throwable;
use RuntimeException;
use Snicco\Mail\Contracts\TransportException;

final class WPMailTransportException extends RuntimeException implements TransportException
{
    
    /**
     * @var string
     */
    private $debug_data;
    
    public function __construct($message = "", string $debug_data = '', Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->debug_data = $debug_data;
    }
    
    public static function becauseWPMailRaisedErrors(WP_Error $error) :WPMailTransportException
    {
        $message = implode("\n", $error->get_error_messages('wp_mail_failed'));
        
        $errors_as_string = '';
        
        foreach ($error->get_all_error_data('wp_mail_failed') as $data) {
            $data = (array) $data;
            
            foreach ($data as $key => $value) {
                if ( ! ($value = (string) $value)) {
                    continue;
                }
                $errors_as_string .= "$key: $value, ";
            }
        }
        
        return new static(
            "wp_mail() failure. Message: [$message]. Data: [$errors_as_string]",
            "Error Data: [$errors_as_string]."
        );
    }
    
    public function getDebugData() :string
    {
        return $this->debug_data;
    }
    
}