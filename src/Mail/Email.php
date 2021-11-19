<?php

declare(strict_types=1);

namespace Snicco\Mail;

use LogicException;
use ReflectionClass;
use ReflectionProperty;
use Snicco\Mail\ValueObjects\From;
use Snicco\Mail\ValueObjects\ReplyTo;
use Snicco\Mail\ValueObjects\Recipient;
use Snicco\Mail\Contracts\MailRenderer;
use Snicco\Mail\ValueObjects\Attachment;
use Snicco\Mail\Contracts\ImmutableEmail;

abstract class Email implements ImmutableEmail
{
    
    /**
     * @var string
     * @api
     */
    protected $subject;
    
    /**
     * @var string
     * @api
     */
    protected $content_type = 'text/html';
    
    /**
     * @var string
     * @api
     */
    protected $message;
    
    /**
     * @var string
     */
    private $view;
    
    /**
     * @var From
     */
    private $from;
    
    /**
     * @var ReplyTo
     */
    private $reply_to;
    
    /**
     * @var Attachment[]
     */
    private $attachments = [];
    
    /**
     * @var array
     */
    private $view_data = [];
    
    abstract public function configure(Recipient $recipient) :void;
    
    /**
     * @internal
     */
    public function compile(MailRenderer $mail_renderer, DefaultConfig $defaults, Recipient $recipient)
    {
        $this->configure($recipient);
        
        if (isset($this->view) && ! empty($this->view)) {
            $context = $this->buildViewData();
            
            $context = array_merge(['recipient' => $recipient], $context);
            
            $this->message = $mail_renderer->getMailContent($this->view, $context);
        }
        
        if ( ! isset($this->from)) {
            $this->from = $defaults->getFrom();
            $this->reply_to = $defaults->getReplyTo();
        }
        
        $this->validate();
    }
    
    /**
     * @return From
     * @api
     */
    public function getFrom() :From
    {
        return $this->from;
    }
    
    /**
     * @return ReplyTo
     * @api
     */
    public function getReplyTo() :ReplyTo
    {
        return $this->reply_to;
    }
    
    /**
     * @return string
     * @api
     */
    public function getContentType() :string
    {
        return $this->content_type;
    }
    
    /**
     * @return string
     * @api
     */
    public function getSubject() :string
    {
        return $this->subject;
    }
    
    /**
     * @return string
     * @api
     */
    public function getMessage() :string
    {
        return $this->message;
    }
    
    /**
     * @return Attachment[]
     * @api
     */
    public function getAttachments() :array
    {
        return $this->attachments;
    }
    
    /**
     * @return $this
     * @api
     */
    protected function message(string $message, string $content_type = 'text/html') :Email
    {
        $this->message = $message;
        $this->content_type = $content_type;
        return $this;
    }
    
    /**
     * @return $this
     * @api
     */
    protected function from(string $email, string $name = '') :Email
    {
        $this->from = new From($email, $name);
        
        return $this;
    }
    
    /**
     * @return $this
     * @api
     */
    protected function replyTo(string $email, string $name = '') :Email
    {
        $this->reply_to = new ReplyTo($email, $name);
        return $this;
    }
    
    /**
     * @return $this
     * @api
     */
    protected function attach(string $file_path, string $file_name = '') :Email
    {
        $this->attachments[] = new Attachment($file_path, $file_name);
        return $this;
    }
    
    /**
     * @return $this
     * @api
     */
    protected function view(string $view_name) :Email
    {
        $this->view = $view_name;
        $this->content_type = 'text/html';
        return $this;
    }
    
    /**
     * @return $this
     * @api
     */
    protected function subject(string $subject) :Email
    {
        $this->subject = $subject;
        return $this;
    }
    
    /**
     * @return $this
     * @api
     */
    protected function text(string $view_name) :Email
    {
        $this->view = $view_name;
        $this->content_type = 'text/plain';
        return $this;
    }
    
    /**
     * @return $this
     * @api
     */
    protected function with($key, $value = null) :Email
    {
        if (is_array($key)) {
            $this->view_data = array_merge($this->view_data, $key);
        }
        else {
            $this->view_data[$key] = $value;
        }
        
        return $this;
    }
    
    private function buildViewData() :array
    {
        $data = $this->view_data;
        
        $properties = (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC);
        
        foreach ($properties as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $data[$property->getName()] = $property->getValue($this);
            }
        }
        
        return $data;
    }
    
    private function validate()
    {
        if (empty($this->subject)) {
            throw new LogicException("The mailable has no subject line.");
        }
        
        if (empty($this->message)) {
            throw new LogicException("The mailable has no message.");
        }
        
        if (empty($this->content_type)) {
            throw new LogicException("The mailable has no content-type.");
        }
    }
    
}