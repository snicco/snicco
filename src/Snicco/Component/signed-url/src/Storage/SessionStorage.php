<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Storage;

use ArrayAccess;
use InvalidArgumentException;
use Snicco\Component\SignedUrl\Exception\BadIdentifier;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;

use function is_array;

final class SessionStorage implements SignedUrlStorage
{
    /**
     * @var string
     */
    private const namespace = '_signed_urls';

    /**
     * @var array|ArrayAccess
     */
    private $storage;

    private Clock $clock;

    /**
     * @param array|ArrayAccess $storage
     *
     * @codeCoverageIgnore
     */
    public function __construct(&$storage, Clock $clock = null)
    {
        if ($storage instanceof ArrayAccess) {
            $this->storage = $storage;
        } elseif (is_array($storage)) {
            // @codeCoverageIgnoreStart Has a weird bug with phpunit. These branches are definitely covered
            $this->storage = &$storage;
        // @codeCoverageIgnoreEnd
        } else {
            // @codeCoverageIgnoreStart
            throw new InvalidArgumentException('$storage must be an array or instance of ArrayAccess');
            // @codeCoverageIgnoreEnd
        }

        $this->clock = $clock ?? SystemClock::fromUTC();
    }

    public function consume(string $identifier): void
    {
        $left_usage = $this->remainingUsage($identifier);
        if ($left_usage < 1) {
            throw BadIdentifier::for($identifier);
        }

        $new_usage = $left_usage - 1;

        $_temp = $this->getStored();

        if (0 === $new_usage) {
            unset($_temp[$identifier]);
        } else {
            $_temp[$identifier]['left_usages'] = $new_usage;
        }

        $this->storage[self::namespace] = $_temp;
    }

    public function store(SignedUrl $signed_url): void
    {
        $data = [
            'expires_at' => $signed_url->expiresAt(),
            'left_usages' => $signed_url->maxUsage(),
        ];

        // $_temp is needed to work with ArrayAccess
        $_temp = $this->getStored();

        $_temp[$signed_url->identifier()] = $data;

        $this->storage[self::namespace] = $_temp;
    }

    public function gc(): void
    {
        $_temp = $this->getStored();
        foreach ($_temp as $id => $data) {
            if (($data['expires_at'] ?? 0) < $this->clock->currentTimestamp()) {
                unset($_temp[$id]);
                $this->storage[self::namespace] = $_temp;
            }
        }
    }

    private function remainingUsage(string $identifier): int
    {
        $stored = $this->getStored();

        if (! isset($stored[$identifier])) {
            return 0;
        }

        return $stored[$identifier]['left_usages'] ?? 0;
    }

    /**
     * @return array<string,array{expires_at: positive-int, left_usages:positive-int}>
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    private function getStored(): array
    {
        return $this->storage[self::namespace] ?? [];
    }
}
