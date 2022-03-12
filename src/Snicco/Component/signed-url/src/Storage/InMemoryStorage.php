<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Storage;

use Snicco\Component\SignedUrl\Exception\BadIdentifier;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;

final class InMemoryStorage implements SignedUrlStorage
{
    /**
     * @var array<string,array{expires_at: int, usages_left: int}>
     */
    private array $links = [];

    private Clock $clock;

    public function __construct(?Clock $clock = null)
    {
        $this->clock = $clock ?? SystemClock::fromUTC();
    }

    public function gc(): void
    {
        foreach ($this->links as $key => $link) {
            if ($link['expires_at'] < $this->clock->currentTimestamp()) {
                unset($this->links[$key]);
            }
        }
    }

    public function store(SignedUrl $signed_url): void
    {
        $this->links[$signed_url->identifier()] = [
            'expires_at' => $signed_url->expiresAt(),
            'usages_left' => $signed_url->maxUsage(),
        ];
    }

    public function consume(string $identifier): void
    {
        if (! isset($this->links[$identifier])) {
            throw BadIdentifier::for($identifier);
        }

        $prev = $this->links[$identifier]['usages_left'];
        $new = $prev - 1;

        if ($new < 1) {
            unset($this->links[$identifier]);
        } else {
            $this->links[$identifier]['usages_left'] = $new;
        }
    }

    public function all(): array
    {
        return $this->links;
    }
}
