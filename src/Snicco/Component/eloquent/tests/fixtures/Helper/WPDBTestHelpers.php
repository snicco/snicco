<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Helper;

use Closure;
use mysqli;

use function is_float;
use function is_int;
use function is_string;
use function mysqli_report;

use const MYSQLI_REPORT_ALL;
use const MYSQLI_REPORT_OFF;

trait WPDBTestHelpers
{
    protected function withDatabaseExceptions(Closure $test): void
    {
        global $wpdb;
        /** @var mysqli $mysqli */
        $mysqli = $wpdb->dbh;

        $mode = $mysqli->query('SELECT @@SESSION.sql_mode');
        $current_default = $mode->fetch_row()[0];

        $mysqli->query("SET SESSION sql_mode='TRADITIONAL'");

        mysqli_report(MYSQLI_REPORT_ALL);

        try {
            $test();
        } finally {
            mysqli_report(MYSQLI_REPORT_OFF);
            $mysqli->query(sprintf("SET SESSION sql_mode='%s'", (string) $current_default));
        }
    }

    protected function assertDbTable(string $table_name): AssertableWpDB
    {
        return new AssertableWpDB($table_name);
    }

    /**
     * NOTE: THIS DATABASE HAS TO EXIST ON THE LOCAL MACHINE.
     */
    protected function secondDatabaseConfig(): array
    {
        return [
            'mysql2' => [
                'driver' => 'mysql',
                'database' => 'sniccowp_2_testing',
                'host' => $_SERVER['SECONDARY_DB_HOST'] ?? '127.0.0.1',
                'username' => $_SERVER['SECONDARY_DB_USER'] ?? 'root',
                'password' => $_SERVER['SECONDARY_DB_PASSWORD'] ?? '',
                'prefix' => 'wp_',
            ],
        ];
    }

    protected function wpdbInsert(string $table, array $data): void
    {
        global $wpdb;

        $format = $this->format($data);

        $success = false !== $wpdb->insert($table, $data, $format);

        if (! $success) {
            $this->fail('Failed to insert with wpdb for test setup.');
        }
    }

    protected function removeWpBrowserTransaction(): void
    {
        global $wpdb;
        $wpdb->query('COMMIT');
    }

    protected function wpdbUpdate(string $table, array $data, array $where): void
    {
        global $wpdb;

        $where_format = $this->format($where);
        $data_format = $this->format($data);

        $success = false !== $wpdb->update($table, $data, $where, $data_format, $where_format);

        if (! $success) {
            $this->fail('Failed to update with wpdb.');
        }
    }

    protected function wpdbDelete(string $table, array $wheres): void
    {
        global $wpdb;

        $wpdb->delete($table, $wheres, $this->format($wheres));
    }

    private function format(array $data): array
    {
        $format = [];
        foreach ($data as $item) {
            if (is_float($item)) {
                $format[] = '%f';
            }

            if (is_int($item)) {
                $format[] = '%d';
            }

            if (is_string($item)) {
                $format[] = '%s';
            }
        }

        return $format;
    }
}
