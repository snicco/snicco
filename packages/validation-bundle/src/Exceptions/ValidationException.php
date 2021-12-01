<?php

declare(strict_types=1);

namespace Snicco\Validation\Exceptions;

use Throwable;
use Snicco\Session\MessageBag;
use Snicco\ExceptionHandling\Exceptions\HttpException;

class ValidationException extends HttpException
{
    
    protected string   $message_for_users = 'We could not process your request.';
    private MessageBag $messages;
    private array      $errors;
    private string     $message_bag_name;
    
    public function __construct(
        array $errors,
        ?string $log_message = 'Failed Validation',
        int $status = 400,
        Throwable $previous = null
    ) {
        parent::__construct($status, $log_message, $previous);
        $this->errors = $errors;
    }
    
    public static function withMessages(
        array $messages,
        string $message_for_users = 'We could not process your request.',
        string $log_message = 'Failed Validation',
        int $status = 400
    ) :ValidationException {
        $bag = new MessageBag($messages);
        $e = new static($messages, $log_message, $status);
        $e->setMessageBag($bag);
        $e->withMessageForUsers($message_for_users);
        
        return $e;
    }
    
    public function setMessageBag(
        MessageBag $message_bag,
        string $name = 'default'
    ) :ValidationException {
        $this->messages = $message_bag;
        $this->message_bag_name = $name;
        
        return $this;
    }
    
    public function setMessageBagName(string $name = 'default') :ValidationException
    {
        $this->message_bag_name = $name;
        return $this;
    }
    
    public function errorsAsArray() :array
    {
        return $this->errors;
    }
    
    public function messages() :MessageBag
    {
        return $this->messages;
    }
    
    public function namedBag() :string
    {
        return $this->message_bag_name;
    }
    
}