<?php

declare(strict_types=1);


namespace Snicco\Bridge\SessionWP;

use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\Exception\NoMatchingRowFound;
use Snicco\Component\Session\Driver\UserSessionsDriver;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\ValueObject\SerializedSession;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;

use function count;
use function intval;
use function is_null;
use function is_numeric;
use function rtrim;
use function str_repeat;
use function strval;

final class WPDBSessionDriver implements UserSessionsDriver
{
    /**
     * @var non-empty-string
     */
    private string $table_name;

    private BetterWPDB $db;

    private Clock $clock;

    /**
     * @param non-empty-string $table_name
     */
    public function __construct(string $table_name, BetterWPDB $db, Clock $clock = null)
    {
        $this->table_name = $table_name;
        $this->db = $db;
        $this->clock = $clock ?: SystemClock::fromUTC();
    }

    public function read(string $selector): SerializedSession
    {
        try {
            $session = $this->db->selectRow(
                "select data, user_id, last_activity, hashed_validator from `$this->table_name` where `selector` = ?",
                [$selector]
            );
        } catch (NoMatchingRowFound $e) {
            throw BadSessionID::forSelector($selector, __CLASS__);
        }

        return $this->instantiate($session);
    }

    public function write(string $selector, SerializedSession $session): void
    {
        $user_id = $session->userId();

        $data = [
            'selector' => $selector,
            'hashed_validator' => $session->hashedValidator(),
            'last_activity' => $session->lastActivity(),
            'data' => $session->data(),
            'user_id' => !is_null($user_id) ? strval($user_id) : null,
        ];

        $this->db->preparedQuery(
            "insert into `$this->table_name` 
                 (selector, hashed_validator, last_activity,data, user_id) 
                 values (?,?,?,?,?) ON DUPLICATE KEY UPDATE data = VALUES(data) , last_activity = VALUES(last_activity), user_id = VALUES(user_id)",
            $data
        );
    }

    public function destroy(array $selectors): void
    {
        $count = count($selectors);
        $placeholders = rtrim(str_repeat('?,', $count), ',');

        $this->db->preparedQuery("delete from `$this->table_name`where selector in ($placeholders)", $selectors);
    }

    public function gc(int $seconds_without_activity): void
    {
        $must_be_newer_than = $this->clock->currentTimestamp() - $seconds_without_activity;

        $this->db->preparedQuery("delete from `$this->table_name` where last_activity <= ?", [$must_be_newer_than]);
    }

    public function touch(string $selector, int $current_timestamp): void
    {
        /** @var non-empty-string $selector */

        $rows = $this->db->updateByPrimary(
            $this->table_name,
            [
                'selector' => $selector,
            ],
            [
                'last_activity' => $current_timestamp,
            ]
        );

        if (0 === $rows && !$this->exists($selector)) {
            throw BadSessionID::forSelector($selector, __CLASS__);
        }
    }

    public function destroyAll(): void
    {
        $this->db->unprepared("delete from `$this->table_name`");
    }

    public function destroyAllForUserId($user_id): void
    {
        $this->db->delete($this->table_name, [
            'user_id' => $user_id,
        ]);
    }

    public function destroyAllForUserIdExcept(string $selector, $user_id): void
    {
        $this->db->preparedQuery(
            "delete from `$this->table_name` where user_id = ? and selector != ? ",
            [$user_id, $selector]
        );
    }

    public function getAllForUserId($user_id): iterable
    {
        $sessions = $this->db->selectAll("select * from $this->table_name where user_id = ?", [$user_id]);
        $sorted = [];

        /**
         * @var array{
         *     data: string,
         *     user_id: string,
         *     last_activity: int,
         *     hashed_validator: string,
         *     selector: string
         * } $session
         */
        foreach ($sessions as $session) {
            $sorted[$session['selector']] = $this->instantiate($session);
        }
        return $sorted;
    }

    public function createTable(): void
    {
        // make user_id a varchar(16) to allow the binary form of a UUID
        $this->db->unprepared(
            "create table if not exists `$this->table_name` (
            `selector` char(24) not null,
            `hashed_validator` char(64) not null,
            `data` text not null,
            `last_activity`int unsigned not null,
            `user_id` varchar(16) default null,
            primary key (`selector`),
            key `user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    }

    private function exists(string $selector): bool
    {
        return $this->db->exists($this->table_name, [
            'selector' => $selector,
        ]);
    }

    private function instantiate(array $data): SerializedSession
    {
        /**
         * @var array{
         *     data: string,
         *     user_id: ?string,
         *     last_activity: int,
         *     hashed_validator: string
         * } $data
         */

        $user_id = $data['user_id'];

        $user_id = is_numeric($user_id) ? intval($user_id) : $user_id;

        return SerializedSession::fromString(
            $data['data'],
            $data['hashed_validator'],
            $data['last_activity'],
            $user_id,
        );
    }
}
