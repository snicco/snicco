<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Helper;

use Closure;
use mysqli;

use function mysqli_report;

use const MYSQLI_REPORT_ALL;
use const MYSQLI_REPORT_OFF;

trait WPDBTestHelpers
{

    protected function withDatabaseExceptions(Closure $test)
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
            $mysqli->query("SET SESSION sql_mode='$current_default'");
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
            'mysql2' =>
                [
                    'driver' => 'mysql',
                    'database' => 'sniccowp_2_testing',
                    'host' => $_SERVER['SECONDARY_DB_HOST'] ?? '127.0.0.1',
                    'username' => $_SERVER['SECONDARY_DB_USER'] ?? 'root',
                    'password' => $_SERVER['SECONDARY_DB_PASSWORD'] ?? '',
                    'prefix' => 'wp_',
                ],
        ];
    }

    protected function wpdbInsert(string $table, array $data)
    {
        global $wpdb;

        $format = $this->format($data);

        $success = $wpdb->insert($table, $data, $format) !== false;

        if (!$success) {
            $this->fail('Failed to insert with wpdb for test setup.');
        }
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

    protected function removeWpBrowserTransaction()
    {
        global $wpdb;
        $wpdb->query('COMMIT');
    }

    protected function wpdbUpdate(string $table, array $data, array $where)
    {
        global $wpdb;

        $where_format = $this->format($where);
        $data_format = $this->format($data);

        $success = $wpdb->update($table, $data, $where, $data_format, $where_format) !== false;

        if (!$success) {
            $this->fail('Failed to update with wpdb.');
        }
    }

    protected function wpdbDelete(string $table, array $wheres)
    {
        global $wpdb;

        $wpdb->delete($table, $wheres, $this->format($wheres));
    }

}