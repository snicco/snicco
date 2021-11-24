<?php

declare(strict_types=1);

namespace Snicco\Mail;

use LogicException;
use InvalidArgumentException;
use Snicco\Mail\Contracts\Mailer;
use Snicco\Mail\Event\EmailWasSent;
use Snicco\Mail\Event\SendingEmail;
use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\ValueObjects\Envelope;
use Snicco\Mail\Contracts\MailRenderer;
use Snicco\Mail\Mailer\WordPressMailer;
use Snicco\Mail\ValueObjects\MailDefaults;
use Snicco\Mail\Renderer\AggregateRenderer;
use Snicco\Mail\Renderer\FilesystemRenderer;
use Snicco\Mail\Contracts\TransportException;
use Snicco\Mail\Contracts\MailEventDispatcher;
use Snicco\Mail\Contracts\MailBuilderInterface;

/**
 * @api
 */
final class MailBuilder implements MailBuilderInterface
{
    
    /**
     * @var Address[]
     */
    private $to = [];
    
    /**
     * @var Address[]
     */
    private $cc = [];
    
    /**
     * @var Address[]
     */
    private $bcc = [];
    
    /**
     * @var MailDefaults
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
     * @var MailEventDispatcher
     */
    private $event_dispatcher;
    
    public function __construct(
        ?Mailer $mailer = null,
        ?MailRenderer $mail_renderer = null,
        ?MailEventDispatcher $event_dispatcher = null,
        ?MailDefaults $default_config = null
    ) {
        $this->mailer = $mailer ?? new WordPressMailer();
        $this->event_dispatcher = $event_dispatcher ?? new NullDispatcher();
        $this->default_config = $default_config ?? MailDefaults::fromWordPressSettings();
        $this->mail_renderer = $mail_renderer ?? new AggregateRenderer(new FilesystemRenderer());
    }
    
    /**
     * @note The received email is NOT the same instance that is being sent.
     * @throws TransportException
     */
    public function send(Email $mail) :void
    {
        $mail = clone $mail;
        $mail->to(...$this->to);
        $mail->cc(...$this->cc);
        $mail->bcc(...$this->bcc);
        
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        
        $mail->configure();
        
        $this->fireSendingEvent($mail);
        
        $this->prepare($mail);
        $this->validate($mail);
        
        $envelope = new Envelope(
            $this->getSender($mail),
            ...array_merge($mail->getTo(), $mail->getCc(), $mail->getBcc())
        );
        
        $this->mailer->send($mail, $envelope);
        
        $this->fireSentEvent($mail, $envelope);
    }
    
    /**
     * @inheritdoc
     */
    public function to($addresses) :MailBuilderInterface
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
        
        $this->to = $this->normalizeAddress($addresses);
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function cc($addresses) :MailBuilderInterface
    {
        $this->cc = $this->normalizeAddress($addresses);
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function bcc($addresses) :MailBuilderInterface
    {
        $this->bcc = $this->normalizeAddress($addresses);
        return $this;
    }
    
    private function normalizeAddress($addresses) :array
    {
        if (is_array($addresses)) {
            $addresses = is_string(array_values($addresses)[0]) ? [$addresses] : $addresses;
        }
        else {
            $addresses = [$addresses];
        }
        
        return Address::createFromArray($addresses);
    }
    
    private function getSender(Email $mail) :?Address
    {
        if ($sender = $mail->getSender()) {
            return $sender;
        }
        
        if ( ! empty($from = $mail->getFrom())) {
            return $from[0];
        }
        
        if ($return = $mail->getReturnPath()) {
            return $return;
        }
        
        return $this->default_config->getFrom();
    }
    
    private function validate(Email $mail)
    {
        if (empty($mail->getSubject())) {
            throw new LogicException(
                sprintf('The mailable [%s] has no subject line.', get_class($mail))
            );
        }
        
        if ($mail->getTextBody() === null && $mail->getHtmlBody() === null
            && ! count(
                $mail->getAttachments()
            )) {
            throw new LogicException('A mailable must have text, html or attachments.');
        }
        
        if ($mail->getPriority() && ( ! $mail->getPriority() > 5 || $mail->getPriority() < 1)) {
            throw new InvalidArgumentException('The priority has to be between 1 and 5.');
        }
    }
    
    // This has to be mutable in order to allow other third-party developers to customize the email.
    private function fireSendingEvent(Email $email)
    {
        $this->event_dispatcher->fireSending(new SendingEmail($email));
    }
    
    // We make a clone of the objects so that no hooked listener can modify them and event other
    // listeners that might fire later.
    private function fireSentEvent(Email $email, Envelope $envelope)
    {
        $this->event_dispatcher->fireSent(new EmailWasSent(clone $email, clone $envelope));
    }
    
    private function prepare(Email $mail)
    {
        if ($html_template = $mail->getHtmlTemplate()) {
            $html = $this->mail_renderer->getMailContent($html_template, $mail->getContext());
            $mail->html($html);
        }
        
        if ($text_template = $mail->getTextTemplate()) {
            $text = $this->mail_renderer->getMailContent($text_template, $mail->getContext());
            $mail->text($text);
        }
        
        if ( ! count($mail->getFrom())) {
            $from = $this->default_config->getFrom();
            $mail->addFrom($from->getAddress(), $from->getName());
        }
        
        if ( ! count($mail->getReplyTo())) {
            $reply_to = $this->default_config->getReplyTo();
            $mail->addReplyTo($reply_to->getAddress(), $reply_to->getName());
        }
    }
    
}

