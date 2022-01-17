<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

use Snicco\Mail\ValueObjects\Address;

interface MutableEmail
{
    
    public function cc(Address ...$addresses) :self;
    
    public function bcc(Address ...$address) :self;
    
    public function to(Address ...$address) :self;
    
    public function subject(string $subject) :self;
    
    public function sender(string $email, string $name = '') :self;
    
    public function returnPath(string $email, string $name = '') :self;
    
    public function addReplyTo(string $email, string $name = '') :self;
    
    public function addFrom(string $email, string $name = '') :self;
    
    public function attachFromPath(string $path, string $name = null, string $content_type = null) :self;
    
    /**
     * @param  string|resource  $data
     */
    public function attach($data, string $name = null, string $content_type = null) :self;
    
    public function embedFromPath(string $path, string $name, string $content_type = null) :self;
    
    /**
     * @param  string|resource  $data
     */
    public function embed($data, string $name, string $content_type = null) :self;
    
    /**
     * @param  string|resource  $html
     */
    public function html($html) :self;
    
    public function context($key, $value = null) :self;
    
    public function textTemplate(string $template_name) :self;
    
    public function htmlTemplate(string $template_name) :self;
    
    /**
     * @param  string|resource  $text
     */
    public function text($text) :self;
    
    public function priority(int $priority) :self;
    
}