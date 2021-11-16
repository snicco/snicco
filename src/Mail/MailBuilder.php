<?php

declare(strict_types=1);

namespace Snicco\Mail;

use Snicco\Support\Arr;
use Snicco\Events\PendingMail;
use Contracts\ContainerAdapter;
use BetterWpHooks\Contracts\Dispatcher;
use Snicco\Support\ReflectionDependencies;

class MailBuilder
{
    
    private Dispatcher       $dispatcher;
    private ContainerAdapter $container;
    
    /**
     * @var object[]
     */
    private array $to = [];
    
    /**
     * @var object[]
     */
    private array $cc = [];
    
    /**
     * @var object[]
     */
    private array $bcc = [];
    
    /**
     * @var <string, callable>
     */
    private $override = [];
    
    public function __construct(Dispatcher $dispatcher, ContainerAdapter $container)
    {
        $this->dispatcher = $dispatcher;
        $this->container = $container;
    }
    
    public function setOverrides(array $override)
    {
        $this->override = $override;
    }
    
    public function send(Mailable $mail)
    {
        return $this->dispatcher->dispatch(new PendingMail($this->fillAttributes($mail)));
    }
    
    public function to($recipients) :MailBuilder
    {
        $this->to = Arr::wrap($recipients);
        
        return $this;
    }
    
    public function cc($recipients) :MailBuilder
    {
        $this->cc = Arr::wrap($recipients);
        
        return $this;
    }
    
    public function bcc($recipients) :MailBuilder
    {
        $this->bcc = Arr::wrap($recipients);
        
        return $this;
    }
    
    private function fillAttributes(Mailable $mail) :Mailable
    {
        $mail = $mail
            ->to($this->to)
            ->cc($this->cc)
            ->bcc($this->bcc);
        
        $deps = (new ReflectionDependencies($this->container))->build([$mail, 'build']);
        
        $mail = $mail->build(...$deps);
        
        return $this->hasOverride($mail) ? $this->getOverride($mail) : $mail;
    }
    
    private function hasOverride(Mailable $mail) :bool
    {
        return isset($this->override[get_class($mail)]);
    }
    
    private function getOverride(Mailable $mail)
    {
        $func = $this->override[$original_class = get_class($mail)];
        
        $new_mailable = call_user_func($func, $mail);
        
        if (get_class($new_mailable) !== $original_class) {
            $deps = (new ReflectionDependencies($this->container))->build([$new_mailable, 'build']);
            
            $new_mailable = $new_mailable->build(...$deps);
        }
        
        return $new_mailable;
    }
    
}