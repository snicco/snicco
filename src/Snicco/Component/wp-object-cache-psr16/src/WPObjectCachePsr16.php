<?php

declare(strict_types=1);

namespace Snicco\Component\WPObjectCachePsr16;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Snicco\Component\WPObjectCachePsr16\Exception\BadKey;
use Snicco\Component\WPObjectCachePsr16\Exception\BadTtl;
use Traversable;

use function array_map;
use function gettype;
use function is_array;
use function is_int;
use function is_string;
use function iterator_to_array;
use function preg_match;
use function restore_error_handler;
use function serialize;
use function set_error_handler;
use function sprintf;
use function unserialize;

use const E_NOTICE;

final class WPObjectCachePsr16 implements CacheInterface
{

    private ScopableWP $wp;

    public function __construct(?ScopableWP $wp = null)
    {
        $this->wp = $wp ?: new ScopableWP();
    }

    /**
     * @throws RuntimeException If the cache contents for the key are malformed and can't be unserialized.
     * @throws BadKey
     *
     * @psalm-suppress MixedAssignment
     */
    public function get($key, $default = null)
    {
        $key = $this->validatedKey($key);

        $content = $this->wp->cacheGet($key, '', true, $found);

        if (false === $content && false === $found) {
            return $default;
        }

        return $this->validatedUnserialize($content, $key);
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function getMultiple($keys, $default = null): array
    {
        if (!is_array($keys)) {
            if (!$keys instanceof Traversable) {
                throw new BadKey(
                    sprintf('$keys must be array or Traversable. Got [%s]', gettype($keys))
                );
            }
            $keys = iterator_to_array($keys, false);
        }

        /** @var string[] $_keys */
        $_keys = [];
        foreach ($keys as $key) {
            $_keys[] = $this->validatedKey($key);
        }

        $res = $this->wp->cacheGetMultiple($_keys);

        $values = [];
        foreach ($res as $key => $value) {
            if ($value === false) {
                $values[$key] = $default;
                continue;
            }
            $values[$key] = $this->validatedUnserialize($value, $key);
        }
        return $values;
    }

    public function clear(): bool
    {
        return $this->wp->cacheFlush();
    }

    /**
     * @note It's not possible for us to set multiple keys in one operation.
     *       There is NOTHING we can do on that front until WordPress core decides to add these
     *       methods as a requirement.
     *
     * @psalm-suppress TypeDoesNotContainType
     * @psalm-suppress MixedAssignment
     */
    public function setMultiple($values, $ttl = null): bool
    {
        if (!is_array($values)) {
            if (!$values instanceof Traversable) {
                throw new BadKey(
                    sprintf('$value must be array or Traversable. Got [%s]', gettype($values))
                );
            }
        }

        $iterator = [];

        // Don't set values in this loop because there might be invalid keys in a later iteration.
        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $key = (string)$key;
            }
            $iterator[$this->validatedKey($key)] = $value;
        }

        $res = true;
        foreach ($iterator as $key => $item) {
            // The double string casting is needed because for psr16 (string) "1" is a valid key while (int) 1 is not.
            if (is_int($key)) {
                $key = (string)$key;
            }
            $res = $this->set($key, $item, $ttl);
        }
        return $res;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $key = $this->validatedKey($key);

        // Setting an item with an expiration <=0 should not mean that its persisted forever
        // like in the messy interface that WordPress provides.
        // To guarantee that ttl conforms to the psr interface we have to delete the cache key.
        if (is_int($ttl) && $ttl <= 0) {
            $this->wp->cacheDelete($key);
            return true;
        }

        if (null === $ttl) {
            $expires = 0;
        } elseif (is_int($ttl)) {
            $expires = $ttl;
        } elseif ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable;
            $end = $now->add($ttl);
            $expires = $end->getTimestamp() - $now->getTimestamp();
        } else {
            throw new BadTtl(
                sprintf('$ttl must be null,integer or DateInterval. Got [%s].', gettype($ttl))
            );
        }

        return $this->wp->cacheSet($key, serialize($value), '', $expires);
    }

    /**
     * @note It's not possible for us to delete multiple keys in one operation.
     *       There is NOTHING we can do on that front until WordPress core decides to add these
     *       methods as a requirement.
     *
     * @param iterable $keys
     * @throws BadKey
     */
    public function deleteMultiple($keys): bool
    {
        if (!is_array($keys)) {
            if (!$keys instanceof Traversable) {
                throw new BadKey(
                    sprintf('$keys must be array or Traversable. Got [%s]', gettype($keys))
                );
            }
            $keys = iterator_to_array($keys, false);
        }

        $res = true;

        $keys = array_map(fn($key) => $this->validatedKey($key), $keys);

        foreach ($keys as $key) {
            $res = $this->delete($key);
        }
        return $res;
    }

    /**
     * @throws BadKey
     */
    public function delete($key): bool
    {
        $key = $this->validatedKey($key);
        $res = $this->wp->cacheDelete($key);
        if (false === $res) {
            // Deleting a value that doesn't exist should return true in the psr-interface.
            // The wp object cache will return false for deleting missing keys.
            if (!$this->has($key)) {
                $res = true;
            }
        }
        return $res;
    }

    /**
     * @throws BadKey
     */
    public function has($key): bool
    {
        $this->validatedKey($key);
        $this->wp->cacheGet($key, '', true, $found);
        return true === $found;
    }

    /**
     * @param int|string|mixed $key
     *
     * @throws BadKey
     */
    private function validatedKey($key): string
    {
        if (!is_string($key)) {
            throw new BadKey(
                sprintf('$key has to be string or integer. Got: [%s]', gettype($key))
            );
        }

        if ('' === $key) {
            throw new BadKey('$key cant be an empty string.');
        }

        if (preg_match('|[\{\}\(\)/\\\@\:]|', $key)) {
            throw new BadKey(
                sprintf(
                    'Invalid key: "%s". The key contains one or more characters reserved for future extension: {}()/\@:',
                    $key
                )
            );
        }

        return $key;
    }

    /**
     *
     * @param string|mixed $content
     * @return mixed
     *
     * @throws RuntimeException If content cant be unserialized
     *
     * @psalm-suppress MixedAssignment
     */
    private function validatedUnserialize($content, string $key)
    {
        if (!is_string($content)) {
            throw new RuntimeException("Cache content for key [$key] was not a serialized string.");
        }

        set_error_handler(function () {
            // do nothing
            return true;
        }, E_NOTICE);

        $parsed = unserialize($content);

        restore_error_handler();

        if (false === $parsed && 'b:0;' !== $content) {
            throw new RuntimeException(
                "Cant unserialize cache content for key [$key].\nValue [$content] is corrupted."
            );
        }
        return $parsed;
    }

}