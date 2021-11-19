<?php

declare(strict_types=1);

namespace Snicco\Mail;

use WP_User;
use stdClass;
use InvalidArgumentException;
use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\Contracts\EmailValidator;

final class AddressFactory
{
    
    private EmailValidator $email_validator;
    
    public function __construct(EmailValidator $email_validator)
    {
        $this->email_validator = $email_validator;
    }
    
    public function create($address) :Address
    {
        if (is_string($address)) {
            if ( ! $this->isFormatted($address)) {
                return new $map_into($recipient);
            }
            // @see https://regexr.com/69uh5
            preg_match_all('/^(?<name>\w+\s?\w+)?\s*<(?<email>[^>]+)>$/', $recipient, $parts);
            return new $map_into($parts['email'][0] ??= '', $parts['name'][0] ??= '');
        }
        if ($recipient instanceof WP_User) {
            return call_user_func([$map_into, 'fromWPUser'], $recipient);
        }
        
        if (is_array($recipient)) {
            if ( ! isset($recipient['name']) || ! isset($recipient['email'])) {
                return new $map_into(...array_values($recipient));
            }
            
            return new $map_into($recipient['email'], $recipient['name']);
        }
        
        if ($recipient instanceof stdClass) {
            return new $map_into($recipient->email, $recipient->name);
        }
        
        throw new InvalidArgumentException(
            'recipient has to be either a string, WP_USER, array or stdclass.'
        );
    }
    
    private function isFormatted(string $recipient) :bool
    {
        return strpos($recipient, '<') !== false
               && strpos($recipient, '>') !== false;
    }
    
}