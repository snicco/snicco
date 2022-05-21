<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlWP;

use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use Snicco\Component\SignedUrl\Exception\BadIdentifier;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;

final class WPDBSignedUrlStorage implements SignedUrlStorage
{
    private BetterWPDB $db;

    /**
     * @var non-empty-string
     */
    private string $table_name;

    private Clock $clock;

    /**
     * @param non-empty-string $table_name
     */
    public function __construct(BetterWPDB $db, string $table_name, Clock $clock = null)
    {
        $this->db = $db;
        $this->table_name = $table_name;
        $this->clock = $clock ?: SystemClock::fromUTC();
    }

    public function consume(string $identifier): void
    {
        try {
            $left_usages = (int) $this->db->selectValue(
                "SELECT `left_usages` FROM `{$this->table_name}` WHERE `selector` = ? AND `left_usages` > 0",
                [$identifier]
            );
        } catch (NoMatchingRowFound $e) {
            throw BadIdentifier::for($identifier);
        }

        --$left_usages;

        if ($left_usages < 1) {
            $this->db->delete($this->table_name, [
                'selector' => $identifier,
            ]);
        } else {
            $this->db->update(
                $this->table_name,
                [
                    'selector' => $identifier,
                ],
                [
                    'left_usages' => $left_usages,
                ]
            );
        }
    }

    public function store(SignedUrl $signed_url): void
    {
        $this->db->insert($this->table_name, [
            'selector' => $signed_url->identifier(),
            'expires' => $signed_url->expiresAt(),
            'left_usages' => $signed_url->maxUsage(),
            'protects' => $signed_url->protects(),
        ]);
    }

    public function gc(): void
    {
        $this->db->preparedQuery(
            "DELETE FROM `{$this->table_name}` WHERE `expires` < ?",
            [$this->clock->currentTimestamp()]
        );
    }

    public function createTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_name}` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`selector` varchar(255) NOT NULL,
`expires` int(11) UNSIGNED NOT NULL,
`left_usages` tinyint unsigned NOT NULL,
`protects` varchar(255) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY (`selector`),
 KEY (`expires`)
)";

        $this->db->unprepared($sql);
    }
}
