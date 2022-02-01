<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObjects;

use InvalidArgumentException;
use LogicException;
use WP_User;

use function array_merge;
use function array_values;
use function is_array;
use function is_string;
use function sprintf;
use function strip_tags;

/**
 * This class is an IMMUTABLE value object representing an email.
 * The method on this class follow a simple convention:
 * 1) $email = $email->withXXX(); Will replace the attributes and return a new object.
 * 2 $email = $email->addXXX(); Will merge the attributes and return a new object.
 *
 * @api
 */
class Email
{

    protected string $subject;
    protected string $html;
    protected string $html_template;
    protected string $text_template;
    protected string $text;
    protected string $text_charset = 'utf-8';
    protected string $html_charset = 'utf-8';
    private array $reserved_context = [
        'images' => 'Its used to generated CIDs in your templates',
    ];

    /**
     * @var Mailbox[]
     */
    private array $to = [];

    /**
     * @var Mailbox[]
     */
    private array $cc = [];

    /**
     * @var Mailbox[]
     */
    private array $bcc = [];

    private Mailbox $sender;

    private Mailbox $return_path;

    /**
     * @var Mailbox[]
     */
    private array $reply_to = [];

    /**
     * @var Mailbox[]
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
     * @param Mailbox|string|array<string,string>|WP_User|WP_User[]|Mailbox[]|array<array<string,string>> $addresses
     */
    final public function withTo($addresses): Email
    {
        $new = clone $this;
        $new->to = $this->normalizeAddress($addresses);
        return $new;
    }

    /**
     * @return Mailbox[]
     *
     * @param (Mailbox|WP_User|string|string[])[]|Mailbox|WP_User|string $addresses
     *
     * @psalm-param Mailbox|WP_User|array<Mailbox|WP_User|array<string, string>|string>|string $addresses
     */
    private function normalizeAddress($addresses): array
    {
        if (is_array($addresses)) {
            $addresses = is_string(array_values($addresses)[0]) ? [$addresses] : $addresses;
        } else {
            $addresses = [$addresses];
        }

        $a = [];
        foreach ($addresses as $address) {
            $a[] = Mailbox::create($address);
        }

        return $a;
    }

    /**
     * @param Mailbox|string|array<string,string>|WP_User|WP_User[]|Mailbox[]|array<array<string,string>> $addresses
     */
    final public function addTo($addresses): Email
    {
        $new = clone $this;
        $new->to = array_merge($this->to, $this->normalizeAddress($addresses));
        return $new;
    }

    /**
     * @param Mailbox|string|array<string,string>|WP_User|WP_User[]|Mailbox[]|array<array<string,string>> $addresses
     */
    final public function withCc($addresses): Email
    {
        $new = clone $this;
        $new->cc = $this->normalizeAddress($addresses);
        return $new;
    }

    /**
     * @param Mailbox|string|array<string,string>|WP_User|WP_User[]|Mailbox[]|array<array<string,string>> $addresses
     */
    final public function addCc($addresses): Email
    {
        $new = clone $this;
        $new->cc = array_merge($this->cc, $this->normalizeAddress($addresses));
        return $new;
    }

    /**
     * @param Mailbox|string|array<string,string>|WP_User|WP_User[]|Mailbox[]|array<array<string,string>> $addresses
     */
    final public function withBcc($addresses): Email
    {
        $new = clone $this;
        $new->bcc = $this->normalizeAddress($addresses);
        return $new;
    }

    /**
     * @param Mailbox|string|array<string,string>|WP_User|WP_User[]|Mailbox[]|array<array<string,string>> $addresses
     */
    final public function addBcc($addresses): Email
    {
        $new = clone $this;
        $new->bcc = array_merge($this->bcc, $this->normalizeAddress($addresses));
        return $new;
    }

    /**
     * @param Mailbox|string|WP_User|array<string,string> $address
     */
    final public function withSender($address): Email
    {
        $new = clone $this;
        $new->sender = Mailbox::create($address);
        return $new;
    }

    /**
     * @param Mailbox|string|WP_User|array<string,string> $address
     */
    final public function withReturnPath($address): Email
    {
        $new = clone $this;
        $new->return_path = Mailbox::create($address);
        return $new;
    }

    final public function withSubject(string $subject): Email
    {
        $new = clone $this;
        $new->subject = $subject;
        return $new;
    }

    /**
     * @param Mailbox|string|array<string,string>|WP_User|WP_User[]|array<array<string,string>> $addresses
     */
    final public function withReplyTo($addresses): Email
    {
        $new = clone $this;
        $new->reply_to = $this->normalizeAddress($addresses);
        return $new;
    }

    /**
     * @param Mailbox|string|array<string,string>|WP_User|WP_User[]|array<array<string,string>> $addresses
     */
    final public function addReplyTo($addresses): Email
    {
        $new = clone $this;
        $new->reply_to = array_merge($this->reply_to, $this->normalizeAddress($addresses));
        return $new;
    }

    /**
     * @param Mailbox|string|array<string,string>|WP_User|WP_User[]|array<array<string,string>> $addresses
     */
    final public function withFrom($addresses): Email
    {
        $new = clone $this;
        $new->from = $this->normalizeAddress($addresses);
        return $new;
    }

    /**
     * @param Mailbox|string|array<string,string>|WP_User|WP_User[]|array<array<string,string>> $addresses
     */
    final public function addFrom($addresses): Email
    {
        $new = clone $this;
        $new->from = array_merge($this->from, $this->normalizeAddress($addresses));
        return $new;
    }

    final public function addAttachment(string $path, string $name = null, string $content_type = null): Email
    {
        $new = clone $this;
        $new->_addAttachment(Attachment::fromPath($path, $name, $content_type));
        return $new;
    }

    /**
     * @api
     */
    final protected function _addAttachment(Attachment $attachment): void
    {
        $this->attachments[] = $attachment;
    }

    /**
     * @param string|resource $data
     */
    final public function addBinaryAttachment($data, string $name = null, string $content_type = null): Email
    {
        $new = clone $this;
        $new->_addAttachment(Attachment::fromData($data, $name, $content_type));
        return $new;
    }

    final public function addEmbed(string $path, string $name = null, string $content_type = null): Email
    {
        $new = clone $this;
        $new->_addAttachment(Attachment::fromPath($path, $name, $content_type, true));
        return $new;
    }

    /**
     * @param false|resource|string $data
     */
    final public function addBinaryEmbed($data, string $name = null, string $content_type = null): Email
    {
        $new = clone $this;
        $new->_addAttachment(Attachment::fromData($data, $name, $content_type, true));
        return $new;
    }

    final public function withPriority(int $priority): Email
    {
        $new = clone $this;
        $new->_setPriority($priority);
        return $new;
    }

    /**
     * @api
     */
    final protected function _setPriority(int $priority): void
    {
        if ($priority < 1 || $priority > 5) {
            throw new InvalidArgumentException('$priority must be an integer between 1 and 5.');
        }
        $this->priority = $priority;
    }

    final public function withHtmlTemplate(string $template): Email
    {
        $new = clone $this;
        $new->html_template = $template;
        return $new;
    }

    final public function withHtmlBody(string $html_body): Email
    {
        $new = clone $this;
        $new->html = $html_body;
        return $new;
    }

    final public function withTextBody(string $text_body): Email
    {
        $new = clone $this;
        $new->text = $text_body;
        return $new;
    }

    final public function withTextTemplate(string $template): Email
    {
        $new = clone $this;
        $new->text_template = $template;
        return $new;
    }

    final public function withContext($key, $value = null): Email
    {
        $new = clone $this;
        $new->context = [];
        $new->_addContext($key, $value);
        return $new;
    }

    /**
     * @api
     */
    final protected function _addContext($key, $value = null): void
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
     * @param string|string[] $key
     * @param null|string $value
     *
     * @psalm-param 'bar'|'foo'|'images'|array{foo: 'FOO', baz: 'BAZ'} $key
     */
    final public function addContext($key, ?string $value = null): Email
    {
        $new = clone $this;
        $new->_addContext($key, $value);
        return $new;
    }

    final public function addCustomHeaders(array $headers): Email
    {
        $new = clone $this;
        foreach ($headers as $name => $value) {
            $new->_addCustomHeader($name, $value);
        }
        return $new;
    }

    /**
     * @api
     */
    final protected function _addCustomHeader(string $name, string $value): void
    {
        $this->custom_headers = array_merge($this->custom_headers, [$name => $value]);
    }

    final public function withCustomHeaders(array $headers): Email
    {
        $new = clone $this;
        $new->custom_headers = [];
        foreach ($headers as $name => $value) {
            $new->_addCustomHeader($name, $value);
        }
        return $new;
    }

    final public function to(): MailboxList
    {
        return new MailboxList($this->to);
    }

    final public function cc(): MailboxList
    {
        return new MailboxList($this->cc);
    }

    final public function bcc(): MailboxList
    {
        return new MailboxList($this->bcc);
    }

    final public function subject(): string
    {
        return $this->subject ?? '';
    }

    final public function sender(): ?Mailbox
    {
        return $this->sender ?? null;
    }

    final public function returnPath(): ?Mailbox
    {
        return $this->return_path ?? null;
    }

    final public function replyTo(): MailboxList
    {
        return new MailboxList($this->reply_to);
    }

    final public function from(): MailboxList
    {
        return new MailboxList($this->from);
    }

    /**
     * @return Attachment[]
     */
    final public function attachments(): array
    {
        return $this->attachments;
    }

    final public function priority(): ?int
    {
        return $this->priority ?? null;
    }

    final public function htmlTemplate(): ?string
    {
        return $this->html_template ?? null;
    }

    final public function textBody(): ?string
    {
        if (isset($this->text)) {
            return $this->text;
        }

        if ($html = $this->htmlBody()) {
            return strip_tags($html);
        }

        return null;
    }

    final public function htmlBody(): ?string
    {
        if (!isset($this->html)) {
            return null;
        }

        return $this->html;
    }

    final public function textTemplate(): ?string
    {
        return $this->text_template ?? null;
    }

    final public function context(): array
    {
        $context = $this->context;

        foreach ($this->attachments as $attachment) {
            if ($attachment->isInline()) {
                $context['images'][$attachment->name()] = $attachment->cid();
            }
        }
        return $context;
    }

    final public function customHeaders(): array
    {
        return $this->custom_headers;
    }

    final public function textCharset(): string
    {
        return $this->text_charset;
    }

    final public function htmlCharset(): string
    {
        return $this->html_charset;
    }

}