<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObject;

use ArrayIterator;
use Countable;
use IteratorAggregate;

use function array_values;
use function count;
use function is_string;

final class MailboxList implements Countable, IteratorAggregate
{
    /**
     * @var array<string,Mailbox>
     */
    private array $addresses = [];

    /**
     * @param Mailbox[] $addresses
     */
    public function __construct(array $addresses)
    {
        foreach ($addresses as $address) {
            $this->addAddress($address);
        }
    }

    /**
     * @param Mailbox[]|MailboxList $list
     */
    public function merge($list): MailboxList
    {
        $new = clone $this;

        foreach ($list as $address) {
            $new->addAddress($address);
        }
        return $new;
    }

    public function count(): int
    {
        return count($this->addresses);
    }

    /**
     * @return ArrayIterator<int, Mailbox>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * @return list<Mailbox>
     */
    public function toArray(): array
    {
        return array_values($this->addresses);
    }

    /**
     * @param string|Mailbox $address
     */
    public function has($address): bool
    {
        $address = is_string($address) ? Mailbox::create($address) : $address;

        return isset($this->addresses[$address->address()]);
    }

    private function addAddress(Mailbox $address): void
    {
        $email = $address->address();

        if (isset($this->addresses[$email])) {
            return;
        }

        $this->addresses[$email] = $address;
    }
}
