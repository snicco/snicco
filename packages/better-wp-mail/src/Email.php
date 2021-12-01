<?php

declare(strict_types=1);

namespace Snicco\Mail;

use ReflectionClass;
use ReflectionProperty;
use InvalidArgumentException;
use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\Contracts\MutableEmail;
use Snicco\Mail\ValueObjects\Attachment;
use Snicco\Mail\Contracts\ImmutableEmail;
use Snicco\Mail\Exceptions\MissingContentIdException;

abstract class Email implements ImmutableEmail, MutableEmail
{
    
    /**
     * @var int Between 1-5, 1 being the highest priority.
     */
    protected $priority;
    
    /**
     * @var string
     */
    protected $subject = '';
    
    /**
     * @var string
     */
    protected $text_charset = 'utf-8';
    
    /**
     * @var string
     */
    protected $html_charset = 'utf-8';
    
    /**
     * @var array
     */
    private $attachments = [];
    
    /**
     * @var Address[];
     */
    private $bcc = [];
    
    /**
     * @var Address[]
     */
    private $cc = [];
    
    /**
     * @var Address[]
     */
    private $to = [];
    
    /**
     * @var Address[]
     */
    private $from = [];
    
    /**
     * @var resource|string|null
     */
    private $html;
    
    /**
     * @var resource|string|null
     */
    private $text;
    
    /**
     * @var Address[]
     */
    private $reply_to = [];
    
    /**
     * @var Address
     */
    private $return_path;
    
    /**
     * @var Address
     */
    private $sender;
    
    /**
     * @var string
     */
    private $text_template;
    
    /**
     * @var string
     */
    private $html_template;
    
    /**
     * @var array
     */
    private $view_context = [];
    
    /**
     * @var array<string,string>
     */
    private $custom_headers = [];
    
    /**
     * @var array
     */
    private $context;
    
    /**
     * @var array
     */
    private $compiled_attachments;
    
    abstract public function configure();
    
    /**
     * @return Attachment[]
     */
    public function getAttachments() :array
    {
        if (isset($this->compiled_attachments)) {
            return $this->compiled_attachments;
        }
        
        $_att = [];
        
        foreach ($this->attachments as $attachment) {
            if (isset($attachment['body'])) {
                $_att[] = Attachment::fromData(
                    $attachment['body'],
                    $attachment['name'] ?? null,
                    $attachment['content-type'] ?? null,
                    $attachment['disposition'] === 'inline'
                );
            }
            else {
                $_att[] = Attachment::fromPath(
                    $attachment['path'] ?? '',
                    $attachment['name'] ?? null,
                    $attachment['content-type'] ?? null,
                    $attachment['disposition'] === 'inline'
                );
            }
        }
        
        $this->compiled_attachments = $_att;
        
        return $_att;
    }
    
    /**
     * @return Address[]
     */
    public function getTo() :array
    {
        return $this->to;
    }
    
    /**
     * @return Address[]
     */
    public function getBcc() :array
    {
        return $this->bcc;
    }
    
    /**
     * @return Address[]
     */
    public function getCc() :array
    {
        return $this->cc;
    }
    
    /**
     * @return Address[]
     */
    public function getFrom() :array
    {
        return $this->from;
    }
    
    /**
     * @return resource|string|null
     */
    public function getHtmlBody()
    {
        return $this->html ?? null;
    }
    
    public function getHtmlCharset() :?string
    {
        return $this->html_charset;
    }
    
    /**
     * @return Address[]
     */
    public function getReplyTo() :array
    {
        return $this->reply_to;
    }
    
    public function getPriority() :?int
    {
        if ( ! isset($this->priority)) {
            return null;
        }
        return $this->priority;
    }
    
    /**
     * @return array<string,string>
     */
    public function getCustomHeaders() :array
    {
        return $this->custom_headers;
    }
    
    public function getReturnPath() :?Address
    {
        return $this->return_path ?? null;
    }
    
    public function getSender() :?Address
    {
        return $this->sender ?? null;
    }
    
    public function getSubject() :string
    {
        return $this->subject;
    }
    
    /**
     * @return resource|string|null
     */
    public function getTextBody()
    {
        return $this->text ?? null;
    }
    
    public function getTextCharset() :?string
    {
        return $this->text_charset;
    }
    
    public function getCid(string $filename) :string
    {
        foreach ($this->getInlineAttachments() as $attachment) {
            if ($attachment->getName() === $filename) {
                return $attachment->getContentId();
            }
        }
        throw new MissingContentIdException(
            sprintf(
                'The mailable [%s] has no embedded attachment with the name: [%s].',
                static::class,
                $filename
            )
        );
    }
    
    public function getContext() :array
    {
        if (isset($this->context)) {
            return $this->context;
        }
        
        $data = $this->view_context;
        
        $properties = (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC);
        
        foreach ($properties as $property) {
            if ($property->getDeclaringClass()->getName() !== self::class) {
                $data[$property->getName()] = $property->getValue($this);
            }
        }
        
        if (count($attachments = $this->getInlineAttachments())) {
            foreach ($attachments as $attachment) {
                $data['images'][$attachment->getName()] = $attachment->getContentId();
            }
        }
        $this->context = $data;
        
        return $this->context;
    }
    
    public function getHtmlTemplate() :?string
    {
        return $this->html_template ?? null;
    }
    
    public function getTextTemplate() :?string
    {
        return $this->text_template ?? null;
    }
    
    public function cc(Address ...$addresses) :MutableEmail
    {
        $this->cc = $addresses;
        return $this;
    }
    
    public function bcc(Address ...$address) :MutableEmail
    {
        $this->bcc = $address;
        return $this;
    }
    
    public function to(Address ...$address) :MutableEmail
    {
        $this->to = $address;
        return $this;
    }
    
    public function subject(string $subject) :MutableEmail
    {
        $this->subject = $subject;
        return $this;
    }
    
    public function priority(int $priority) :MutableEmail
    {
        $this->priority = $priority;
        return $this;
    }
    
    public function sender(string $email, string $name = '') :MutableEmail
    {
        $this->sender = Address::create([$email, $name]);
        return $this;
    }
    
    public function returnPath(string $email, string $name = '') :MutableEmail
    {
        $this->return_path = Address::create([$email, $name]);
        return $this;
    }
    
    public function addReplyTo(string $email, string $name = '') :MutableEmail
    {
        $this->reply_to[] = Address::create([$email, $name]);
        return $this;
    }
    
    public function addFrom(string $email, string $name = '') :MutableEmail
    {
        $this->from[] = Address::create([$email, $name]);
        return $this;
    }
    
    public function attachFromPath(string $path, string $name = null, string $content_type = null) :MutableEmail
    {
        $this->attachments[] = [
            'path' => $path,
            'name' => $name,
            'content-type' => $content_type,
            'disposition' => 'attachment',
        ];
        return $this;
    }
    
    /**
     * @param  string|resource  $data
     */
    public function attach($data, string $name = null, string $content_type = null) :MutableEmail
    {
        $this->attachments[] = [
            'body' => $data,
            'name' => $name,
            'content-type' => $content_type,
            'disposition' => 'attachment',
        ];
        return $this;
    }
    
    public function embedFromPath(string $path, string $name, string $content_type = null) :MutableEmail
    {
        $this->attachments[] = [
            'path' => $path,
            'name' => $name,
            'content-type' => $content_type,
            'disposition' => 'inline',
        ];
        return $this;
    }
    
    /**
     * @param  string|resource  $data
     */
    public function embed($data, string $name, string $content_type = null) :MutableEmail
    {
        $this->attachments[] = [
            'body' => $data,
            'name' => $name,
            'content-type' => $content_type,
            'disposition' => 'inline',
        ];
        return $this;
    }
    
    /**
     * @param  string|resource  $html
     */
    public function html($html) :MutableEmail
    {
        if ( ! is_string($html) && ! is_resource($html)) {
            throw new InvalidArgumentException("Html value must be resource or string.");
        }
        $this->html = $html;
        return $this;
    }
    
    public function context($key, $value = null) :MutableEmail
    {
        if (is_array($key)) {
            $this->view_context = array_merge($this->view_context, $key);
        }
        else {
            $this->view_context[$key] = $value;
        }
        
        return $this;
    }
    
    public function textTemplate(string $template_name) :MutableEmail
    {
        $this->text_template = $template_name;
        return $this;
    }
    
    public function htmlTemplate(string $template_name) :MutableEmail
    {
        $this->html_template = $template_name;
        return $this;
    }
    
    /**
     * @param  string|resource  $text
     */
    public function text($text) :MutableEmail
    {
        if ( ! is_string($text) && ! is_resource($text)) {
            throw new InvalidArgumentException('text value must be resource or string.');
        }
        $this->text = $text;
        return $this;
    }
    
    /**
     * @return Attachment[]
     */
    private function getInlineAttachments() :array
    {
        $inline = [];
        
        $attachments = $this->getAttachments();
        
        foreach ($attachments as $attachment) {
            if ($attachment->getDisposition() === 'inline') {
                $inline[] = $attachment;
            }
        }
        
        return $inline;
    }
    
}