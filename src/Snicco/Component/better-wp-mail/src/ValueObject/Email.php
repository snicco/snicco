<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObject;

use InvalidArgumentException;
use LogicException;
use WP_User;

use function array_key_first;
use function array_merge;
use function is_array;
use function is_string;
use function sprintf;
use function strip_tags;

/**
 * This class is an IMMUTABLE value object representing an email.
 * The method on this class follow a simple convention:
 * 1) $email = $email->withXXX(); Will replace the attributes and return a new object.
 * 2 $email = $email->addXXX(); Will merge the attributes and return a new object.
 */
class Email
{

    protected string $subject = '';
    protected ?string $html = null;
    protected ?string $html_template = null;
    protected ?string $text_template = null;
    protected ?string $text = null;
    protected string $text_charset = 'utf-8';
    protected string $html_charset = 'utf-8';

    /**
     * @var array<string,string>
     */
    private array $reserved_context = [
        'images' => 'Its used to generated CIDs in your templates',
    ];

    /**
     * @var list<Mailbox>
     */
    private array $to = [];

    /**
     * @var list<Mailbox>
     */
    private array $cc = [];

    /**
     * @var list<Mailbox>
     */
    private array $bcc = [];

    private ?Mailbox $sender = null;

    private ?Mailbox $return_path = null;

    /**
     * @var list<Mailbox>
     */
    private array $reply_to = [];

    /**
     * @var list<Mailbox>
     */
    private array $from = [];

    /**
     * @var list<Attachment>
     */
    private array $attachments = [];

    private ?int $priority = null;

    /**
     * @var array<string,mixed>
     */
    private array $context = [];

    /**
     * @var array<string,string>
     */
    private array $custom_headers = [];

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User|WP_User[]|Mailbox[]|T[] $addresses
     */
    final public function withTo($addresses): Email
    {
        $new = clone $this;
        $new->to = $this->normalizeAddresses($addresses);
        return $new;
    }

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User $address
     */
    final public function addTo($address): Email
    {
        $new = clone $this;
        $new->to = array_merge($this->to, $this->normalizeAddresses($address));
        return $new;
    }

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User|WP_User[]|Mailbox[]|T[] $addresses
     */
    final public function withCc($addresses): Email
    {
        $new = clone $this;
        $new->cc = $this->normalizeAddresses($addresses);
        return $new;
    }

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User $address
     */
    final public function addCc($address): Email
    {
        $new = clone $this;
        $new->cc = array_merge($this->cc, $this->normalizeAddresses($address));
        return $new;
    }

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User|WP_User[]|Mailbox[]|T[] $addresses
     */
    final public function withBcc($addresses): Email
    {
        $new = clone $this;
        $new->bcc = $this->normalizeAddresses($addresses);
        return $new;
    }

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User $address
     */
    final public function addBcc($address): Email
    {
        $new = clone $this;
        $new->bcc = array_merge($this->bcc, $this->normalizeAddresses($address));
        return $new;
    }

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User $address
     */
    final public function withSender($address): Email
    {
        $new = clone $this;
        $new->sender = Mailbox::create($address);
        return $new;
    }

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User $address
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
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User|WP_User[]|Mailbox[]|T[] $addresses
     */
    final public function withReplyTo($addresses): Email
    {
        $new = clone $this;
        $new->reply_to = $this->normalizeAddresses($addresses);
        return $new;
    }

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User $address
     */
    final public function addReplyTo($address): Email
    {
        $new = clone $this;
        $new->reply_to = array_merge($this->reply_to, $this->normalizeAddresses($address));
        return $new;
    }

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User|WP_User[]|Mailbox[]|T[] $addresses
     */
    final public function withFrom($addresses): Email
    {
        $new = clone $this;
        $new->from = $this->normalizeAddresses($addresses);
        return $new;
    }

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param Mailbox|string|T|WP_User $address
     */
    final public function addFrom($address): Email
    {
        $new = clone $this;
        $new->from = array_merge($this->from, $this->normalizeAddresses($address));
        return $new;
    }

    final public function addAttachment(string $path, string $name = null, string $content_type = null): Email
    {
        $new = clone $this;
        $new->_addAttachment(Attachment::fromPath($path, $name, $content_type));
        return $new;
    }

    /**
     * @param string|resource $data
     */
    final public function addBinaryAttachment($data, string $name, string $content_type = null): Email
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
     * @param resource|string $data
     */
    final public function addBinaryEmbed($data, string $name, string $content_type = null): Email
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

    /**
     * @param string|array<string,mixed> $key
     * @param mixed $value
     */
    final public function withContext($key, $value = null): Email
    {
        $new = clone $this;
        $new->context = [];
        $new->_addContext($key, $value);
        return $new;
    }

    /**
     * @param string|array<string,mixed> $key
     * @param mixed $value
     */
    final public function addContext($key, $value = null): Email
    {
        $new = clone $this;
        $new->_addContext($key, $value);
        return $new;
    }

    /**
     * @param array<string,string> $headers
     * @return Email
     */
    final public function addCustomHeaders(array $headers): Email
    {
        $new = clone $this;
        foreach ($headers as $name => $value) {
            $new->_addCustomHeader($name, $value);
        }
        return $new;
    }

    /**
     * @param array<string,string> $headers
     * @return Email
     */
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
        return $this->subject;
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

    /**
     * @return array<string,mixed>
     * @psalm-suppress MixedArrayAssignment
     */
    final public function context(): array
    {
        $context = $this->context;

        foreach ($this->attachments as $attachment) {
            if ($attachment->isInline()) {
                $cid = $attachment->cid();
                $context['images'][$attachment->name()] = $cid;
            }
        }
        return $context;
    }

    /**
     * @return  array<string,string>
     */
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

    /**
     * @api
     */
    final protected function _addAttachment(Attachment $attachment): void
    {
        $this->attachments[] = $attachment;
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

    /**
     * @param string|array<string,mixed> $key
     * @param mixed $value
     * @api
     *
     * @psalm-suppress MixedAssignment
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
     * @api
     */
    final protected function _addCustomHeader(string $name, string $value): void
    {
        $this->custom_headers = array_merge($this->custom_headers, [$name => $value]);
    }

    /**
     * @template T as array{0:string, 1:string}|array{email:string, name:string}
     * @param string|Mailbox|WP_User|T|WP_User[]|Mailbox[]|T[] $addresses
     * @return list<Mailbox>
     *
     * @psalm-suppress PossiblyUndefinedArrayOffset
     * @psalm-suppress PossiblyInvalidArgument
     */
    private function normalizeAddresses($addresses): array
    {
        if (is_array($addresses)) {
            $first_key = array_key_first($addresses);
            if (null === $first_key) {
                return [];
            }
            if (is_string($addresses[$first_key])) {
                $addresses = [$addresses];
            }
        }

        $addresses = is_array($addresses) ? $addresses : [$addresses];

        $a = [];
        foreach ($addresses as $address) {
            $a[] = Mailbox::create($address);
        }

        return $a;
    }

}