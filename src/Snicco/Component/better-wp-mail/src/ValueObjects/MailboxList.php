<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObjects;

use ArrayIterator;
use Countable;
use IteratorAggregate;

use function array_values;
use function count;
use function is_string;

/**
 * @api
 */
final class MailboxList implements Countable, IteratorAggregate
{

    /**
     * @var array<string,Mailbox>
     */
    private array $addresses = [];

    public function __construct(array $addresses)
    {
        foreach ($addresses as $address) {
            $this->addAddress($address);
        }
    }

    private function addAddress(Mailbox $address)
    {
        $email = $address->address();

        if (isset($this->addresses[$email])) {
            return;
        }

        $this->addresses[$email] = $address;
    }

    /**
     * @param Mailbox[]|MailboxList $list
     *
     * @return MailboxList
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
     * @return ArrayIterator|Mailbox[]
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_values($this->addresses));
    }

    /**
     * @param string|Mailbox $address
     *
     * @return bool
     */
    public function has($address): bool
    {
        $address = is_string($address) ? Mailbox::create($address) : $address;

        return isset($this->addresses[$address->address()]);
    }

}