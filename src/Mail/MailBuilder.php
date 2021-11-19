<?php

declare(strict_types=1);

namespace Snicco\Mail;

use LogicException;
use Snicco\Mail\ValueObjects\CC;
use Snicco\Mail\ValueObjects\BCC;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Mail\ValueObjects\CCs;
use Snicco\Mail\ValueObjects\BCCs;
use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\ValueObjects\Recipient;
use Snicco\Mail\Contracts\MailRenderer;
use Snicco\Mail\ValueObjects\Recipients;
use Snicco\Mail\Contracts\EmailValidator;
use Snicco\Mail\Contracts\MailBuilderInterface;
use Snicco\Mail\Implementations\WordPressMailer;
use Snicco\Mail\Implementations\AggregateRenderer;
use Snicco\Mail\Implementations\FilesystemRenderer;
use Snicco\Mail\Implementations\FilterVarEmailValidator;

/**
 * @api
 */
final class MailBuilder implements MailBuilderInterface
{
    
    /**
     * @var Recipient[]
     */
    private $to = [];
    
    /**
     * @var CC[]
     */
    private $cc = [];
    
    /**
     * @var BCC[]
     */
    private $bcc = [];
    
    /**
     * @var DefaultConfig
     */
    private $default_config;
    
    /**
     * @var Mailer
     */
    private $mailer;
    
    /**
     * @var MailRenderer
     */
    private $mail_renderer;
    
    /**
     * @var AddressFactory
     */
    private $address_factory;
    
    public function __construct(
        Mailer $mailer = null,
        MailRenderer $mail_renderer = null,
        ?DefaultConfig $default_config = null,
        ?EmailValidator $email_validator = null
    ) {
        $this->mailer = $mailer ?? new WordPressMailer();
        $this->default_config = $default_config ?? DefaultConfig::fromWordPressSettings();
        $this->mail_renderer = $mail_renderer ?? new AggregateRenderer(new FilesystemRenderer());
        $this->address_factory =
            new AddressFactory($email_validator ?? new FilterVarEmailValidator());
    }
    
    public function send(Email $mail)
    {
        foreach ($this->to as $recipient) {
            $copy = clone $mail;
            $copy->compile($this->mail_renderer, $this->default_config, $recipient);
            $this->mailer->send(
                $copy,
                new Recipients($recipient),
                new CCs(...$this->cc),
                new BCCs(...$this->bcc)
            );
        }
        
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
    }
    
    public function to($recipients) :MailBuilderInterface
    {
        if (count($this->cc)) {
            throw new LogicException(
                "[\Snicco\Mail\MailBuilder,cc] should not be called before [\Snicco\Mail\MailBuilder,to]."
            );
        }
        if (count($this->bcc)) {
            throw new LogicException(
                "[\Snicco\Mail\MailBuilder,bcc] should not be called before [\Snicco\Mail\MailBuilder,to]."
            );
        }
        
        $this->to = $this->normalizeAddress($recipients, Recipient::class);
        return $this;
    }
    
    public function cc($recipients) :MailBuilderInterface
    {
        $this->cc = $this->normalizeAddress($recipients, CC::class);
        return $this;
    }
    
    public function bcc($recipients) :MailBuilderInterface
    {
        $this->bcc = $this->normalizeAddress($recipients, BCC::class);
        return $this;
    }
    
    private function normalizeAddress($recipients, string $map_into) :array
    {
        if (is_array($recipients)) {
            $recipients = is_string(array_values($recipients)[0]) ? [$recipients] : $recipients;
        }
        else {
            $recipients = [$recipients];
        }
        
        return array_map(function ($recipient) use ($map_into) {
            return Address::normalize($recipient, $map_into);
        }, $recipients);
    }
    
}