<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObjects;

use WP_User;
use LogicException;
use InvalidArgumentException;

use function sprintf;
use function is_array;
use function is_string;
use function strip_tags;
use function array_merge;
use function array_values;

/**
 * @api
 */
class Email
{
    
    protected string $subject;
    protected string $html;
    protected string $html_template;
    protected string $text_template;
    protected string $text;
    protected string $text_charset     = 'utf-8';
    protected string $html_charset     = 'utf-8';
    private array    $reserved_context = [
        'images' => 'Its used to generated CIDs in your templates',
    ];
    /**
     * @var Address[]
     */
    private array $to = [];
    
    /**
     * @var Address[]
     */
    private array $cc = [];
    
    /**
     * @var Address[]
     */
    private array $bcc = [];
    
    private Address $sender;
    
    private Address $return_path;
    
    /**
     * @var Address[]
     */
    private array $reply_to = [];
    
    /**
     * @var Address[]
     */
    private array $from = [];
    
    /**
     * @var Attachment[]
     */
    private array $attachments = [];
    
    private int $priority;
    
    private array $context = [];
    
    /**
     * @var array<string,string>
     */
    private array $custom_headers = [];
    
    /**
     * @param  Address|string|array<string,string>|WP_User|WP_User[]|Address[]|array<array<string,string>>  $addresses
     */
    final public function withTo($addresses) :Email
    {
        $new = clone $this;
        $new->to = array_merge($this->to, $this->normalizeAddress($addresses));
        return $new;
    }
    
    /**
     * @param  Address|string|array<string,string>|WP_User|WP_User[]|Address[]|array<array<string,string>>  $addresses
     */
    final public function withCc($addresses) :Email
    {
        $new = clone $this;
        $new->cc = array_merge($this->cc, $this->normalizeAddress($addresses));
        return $new;
    }
    
    /**
     * @param  Address|string|array<string,string>|WP_User|WP_User[]|Address[]|array<array<string,string>>  $addresses
     */
    final public function withBcc($addresses) :Email
    {
        $new = clone $this;
        $new->bcc = array_merge($this->bcc, $this->normalizeAddress($addresses));
        return $new;
    }
    
    /**
     * @param  Address|string|WP_User|array<string,string>  $address
     */
    final public function withSender($address) :Email
    {
        $new = clone $this;
        $new->sender = Address::create($address);
        return $new;
    }
    
    /**
     * @param  Address|string|WP_User|array<string,string>  $address
     */
    final public function withReturnPath($address) :Email
    {
        $new = clone $this;
        $new->return_path = Address::create($address);
        return $new;
    }
    
    final public function withSubject(string $subject) :Email
    {
        $new = clone $this;
        $new->subject = $subject;
        return $new;
    }
    
    /**
     * @param  Address|string|array<string,string>|WP_User|WP_User[]|array<array<string,string>>  $addresses
     */
    final public function withReplyTo($addresses) :Email
    {
        $new = clone $this;
        $new->reply_to = array_merge($this->reply_to, $this->normalizeAddress($addresses));
        return $new;
    }
    
    /**
     * @param  Address|string|array<string,string>|WP_User|WP_User[]|array<array<string,string>>  $addresses
     */
    final public function withFrom($addresses) :Email
    {
        $new = clone $this;
        $new->from = array_merge($this->from, $this->normalizeAddress($addresses));
        return $new;
    }
    
    final public function withAttachment(string $path, string $name = null, string $content_type = null) :Email
    {
        $new = clone $this;
        $new->addAttachment(Attachment::fromPath($path, $name, $content_type));
        return $new;
    }
    
    /**
     * @param  string|resource  $data
     */
    final public function withBinaryAttachment($data, string $name = null, string $content_type = null) :Email
    {
        $new = clone $this;
        $new->addAttachment(Attachment::fromData($data, $name, $content_type));
        return $new;
    }
    
    final public function withEmbed(string $path, string $name = null, string $content_type = null) :Email
    {
        $new = clone $this;
        $new->addAttachment(Attachment::fromPath($path, $name, $content_type, true));
        return $new;
    }
    
    final public function withBinaryEmbed($data, string $name = null, string $content_type = null) :Email
    {
        $new = clone $this;
        $new->addAttachment(Attachment::fromData($data, $name, $content_type, true));
        return $new;
    }
    
    final public function withPriority(int $priority) :Email
    {
        $new = clone $this;
        $new->setPriority($priority);
        return $new;
    }
    
    final public function withHtmlTemplate(string $template) :Email
    {
        $new = clone $this;
        $new->html_template = $template;
        return $new;
    }
    
    final public function withHtmlBody(string $html_body) :Email
    {
        $new = clone $this;
        $new->html = $html_body;
        return $new;
    }
    
    final public function withTextBody(string $text_body) :Email
    {
        $new = clone $this;
        $new->text = $text_body;
        return $new;
    }
    
    final public function withTextTemplate(string $template) :Email
    {
        $new = clone $this;
        $new->text_template = $template;
        return $new;
    }
    
    final public function withContext($key, $value = null) :Email
    {
        $new = clone $this;
        $new->addContext($key, $value);
        return $new;
    }
    
    final public function withCustomHeaders(array $headers) :Email
    {
        $new = clone $this;
        foreach ($headers as $name => $value) {
            $new->addCustomHeader($name, $value);
        }
        return $new;
    }
    
    final public function getTo() :AddressList
    {
        return new AddressList($this->to);
    }
    
    final public function getCc() :AddressList
    {
        return new AddressList($this->cc);
    }
    
    final public function getBcc() :AddressList
    {
        return new AddressList($this->bcc);
    }
    
    final public function subject() :string
    {
        return $this->subject ?? '';
    }
    
    final public function sender() :?Address
    {
        return $this->sender ?? null;
    }
    
    final public function returnPath() :?Address
    {
        return $this->return_path ?? null;
    }
    
    final public function replyTo() :AddressList
    {
        return new AddressList($this->reply_to);
    }
    
    final public function from() :AddressList
    {
        return new AddressList($this->from);
    }
    
    /**
     * @return Attachment[]
     */
    final public function attachments() :array
    {
        return $this->attachments;
    }
    
    final public function priority() :?int
    {
        return $this->priority ?? null;
    }
    
    final public function htmlTemplate() :?string
    {
        return $this->html_template ?? null;
    }
    
    final public function htmlBody() :?string
    {
        if ( ! isset($this->html)) {
            return null;
        }
        
        return $this->html;
    }
    
    final public function textBody() :?string
    {
        if (isset($this->text)) {
            return $this->text;
        }
        
        if ($html = $this->htmlBody()) {
            return strip_tags($html);
        }
        
        return null;
    }
    
    final public function textTemplate() :?string
    {
        return $this->text_template ?? null;
    }
    
    final public function context() :array
    {
        $context = $this->context;
        
        foreach ($this->attachments as $attachment) {
            if ($attachment->isInline()) {
                $context['images'][$attachment->name()] = $attachment->cid();
            }
        }
        return $context;
    }
    
    final public function customHeaders() :array
    {
        return $this->custom_headers;
    }
    
    final public function textCharset() :string
    {
        return $this->text_charset;
    }
    
    final public function htmlCharset() :string
    {
        return $this->html_charset;
    }
    
    /**
     * @api
     */
    final protected function addAttachment(Attachment $attachment)
    {
        $this->attachments[] = $attachment;
    }
    
    /**
     * @api
     */
    final protected function setPriority(int $priority)
    {
        if ($priority < 1 || $priority > 5) {
            throw new InvalidArgumentException('$priority must be an integer between 1 and 5.');
        }
        $this->priority = $priority;
    }
    
    /**
     * @api
     */
    final protected function addContext($key, $value = null)
    {
        $context = is_array($key) ? $key : [$key => $value];
        
        foreach ($context as $key => $value) {
            if (isset($this->reserved_context[$key])) {
                throw new LogicException(
                    sprintf(
                        "[%s] is a reserved context key.\n[%s].\nPlease choose a different key.",
                        $key,
                        $this->reserved_context[$key]
                    )
                );
            }
            $this->context[$key] = $value;
        }
    }
    
    /**
     * @api
     */
    final protected function addCustomHeader(string $name, string $value)
    {
        $this->custom_headers = array_merge($this->custom_headers, [$name => $value]);
    }
    
    /**
     * @return Address[]
     */
    private function normalizeAddress($addresses) :array
    {
        if (is_array($addresses)) {
            $addresses = is_string(array_values($addresses)[0]) ? [$addresses] : $addresses;
        }
        else {
            $addresses = [$addresses];
        }
        
        $a = [];
        foreach ($addresses as $address) {
            $a[] = Address::create($address);
        }
        
        return $a;
    }
    
}