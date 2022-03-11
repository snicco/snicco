<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObject;

use InvalidArgumentException;
use LogicException;
use WP_User;

use function call_user_func;
use function filter_var;
use function gettype;
use function is_array;
use function is_bool;
use function is_string;
use function sprintf;

use const FILTER_VALIDATE_EMAIL;

/**
 * Represents an "email mailbox". By default, the domain part is validated using
 * the filter_var. For better validation it is recommended to use
 * https://github.com/egulias/EmailValidator.
 */
final class Mailbox
{
    /**
     * @see https://regexr.com/69uh5
     */
    private const PATTERN = '/^(?<name>\w+\s?\w+)?\s*<(?<address>[^>]+)>$/';

    /**
     * @var callable(string):bool|null
     */
    public static $email_validator;

    private string $address;

    private string $name;

    private function __construct(string $address, string $name = '')
    {
        $address = strtolower($address);

        if (null === self::$email_validator) {
            self::$email_validator = function (string $email): bool {
                return false !== filter_var($email, FILTER_VALIDATE_EMAIL);
            };
        }

        if (! $this->isEmailValid($address, self::$email_validator)) {
            throw new InvalidArgumentException("[{$address}] is not a valid email.");
        }

        $this->address = $address;
        $this->name = ucwords(trim(str_replace(["\n", "\r"], '', $name)));
    }

    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @param array{0:string, 1:string}|array{email:string, name:string}|Mailbox|string|WP_User $address
     */
    public static function create($address): Mailbox
    {
        if ($address instanceof Mailbox) {
            return $address;
        }

        if (is_string($address)) {
            if (false === strpos($address, '<')) {
                return new self($address);
            }

            if (! preg_match(self::PATTERN, $address, $matches)) {
                throw new InvalidArgumentException(sprintf('[%s] is not a valid address', $address));
            }

            return new self($matches['address'], $matches['name']);
        }

        if (is_array($address)) {
            if (isset($address['name'], $address['email'])) {
                return new self($address['email'], $address['name']);
            }

            return new self(...array_values($address));
        }

        if (! $address instanceof WP_User) {
            throw new InvalidArgumentException(
                sprintf('$address has to be string,array or an instance of WP_User. Got [%s].', gettype($address))
            );
        }

        if ($address->first_name) {
            $name = $address->first_name . ' ' . $address->last_name ?: '';
        } else {
            $name = $address->display_name ?: '';
        }

        return new self($address->user_email, $name);
    }

    public function toString(): string
    {
        return ($name = $this->name())
            ? ($name . ' <' . $this->address() . '>')
            : $this->address();
    }

    public function name(): string
    {
        return $this->name;
    }

    public function address(): string
    {
        return $this->address;
    }

    private function isEmailValid(string $address, callable $validator): bool
    {
        $res = call_user_func($validator, $address);
        if (! is_bool($res)) {
            throw new LogicException("MailBox::email_validator did not return a boolean for address [{$address}].");
        }

        return $res;
    }
}
