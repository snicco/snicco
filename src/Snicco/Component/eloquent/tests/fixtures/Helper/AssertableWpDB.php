<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Helper;

use PHPUnit\Framework\Assert as PHPUnit;
use wpdb;

use function is_float;
use function is_int;
use function is_string;

/**
 * @internal
 *
 * @psalm-internal Snicco\Component\Eloquent
 */
final class AssertableWpDB
{
    private string $table;

    private wpdb $wpdb;

    public function __construct(string $table)
    {
        $this->table = $table;
        $this->wpdb = $GLOBALS['wpdb'];
    }

    /**
     * @param array<string,scalar|null> $column_conditions
     */
    public function assertRecordExists(array $column_conditions): void
    {
        $wheres = '';
        $values = [];

        foreach ($column_conditions as $column => $value) {
            if ('' !== $wheres) {
                $wheres .= ' AND ';
            }

            if (is_float($value)) {
                $wheres .= $column . ' = %f';
            }

            if (is_int($value)) {
                $wheres .= $column . ' = %d';
            }

            if (is_string($value)) {
                $wheres .= $column . ' = %s';
            }

            $values[] = $value;
        }

        /** @var string $query */
        $query = $this->wpdb->prepare(
            sprintf('SELECT EXISTS(SELECT 1 FROM %s WHERE %s LIMIT 1)', $this->table, $wheres),
            $values
        );

        $exists = $this->wpdb->get_var($query);

        $result = (is_string($exists) && '1' === $exists);

        $record_as_string = '';

        foreach ($column_conditions as $column => $value) {
            $record_as_string .= sprintf('%s => %s,', $column, (string) $value);
        }

        $record_as_string = trim($record_as_string, ',');

        PHPUnit::assertTrue(
            $result,
            sprintf('The record [%s] was not found in the table [%s].', $record_as_string, $this->table)
        );
    }

    /**
     * @param array<string,scalar|null> $column_conditions
     */
    public function assertRecordNotExists(array $column_conditions): void
    {
        [$wheres, $values] = $this->compile($column_conditions);

        /** @var string $query */
        $query = $this->wpdb->prepare(
            sprintf('SELECT EXISTS(SELECT 1 FROM %s WHERE %s LIMIT 1)', $this->table, $wheres),
            $values
        );

        $exists = $this->wpdb->get_var($query);

        $record_as_string = '';

        foreach ($column_conditions as $column => $value) {
            $record_as_string .= sprintf('%s => %s,', $column, (string) $value);
        }

        $record_as_string = trim($record_as_string, ',');

        PHPUnit::assertSame(
            '0',
            $exists,
            sprintf('The record [%s] was unexpectedly found in the table [%s].', $record_as_string, $this->table)
        );
    }

    /**
     * @param array<string,scalar> $expected
     * @param array<string,scalar> $conditions
     */
    public function assertRecordEquals(array $conditions, array $expected): void
    {
        [$wheres, $values] = $this->compile($conditions);

        $record = $this->wpdb->get_row(
            (string) $this->wpdb->prepare(sprintf('SELECT * FROM %s WHERE %s LIMIT 1', $this->table, $wheres), $values),
            'ARRAY_A'
        );

        PHPUnit::assertSame($expected, $record, 'The record does not exists as specified.');
    }

    public function assertTotalCount(int $int): void
    {
        $query = sprintf('SELECT COUNT(*) FROM %s', $this->table);

        $result = (int) $this->wpdb->get_var($query);

        PHPUnit::assertSame(
            $int,
            $result,
            sprintf('The expected count [%d] does not match the actual count [%d].', $int, $result)
        );
    }

    public function assertCountWhere(array $column_conditions, int $count): void
    {
        [$wheres, $values] = $this->compile($column_conditions);

        /** @var string $query */
        $query = $this->wpdb->prepare(sprintf('SELECT COUNT(*) FROM %s WHERE %s', $this->table, $wheres), $values);

        $actual_count = (int) $this->wpdb->get_var($query);

        PHPUnit::assertSame(
            $count,
            $actual_count,
            sprintf('The expected count [%d] does not match the actual count [%d].', $count, $actual_count)
        );
    }

    /**
     * @return array{0:string, 1:array}
     */
    private function compile(array $conditions): array
    {
        $wheres = '';
        $values = [];

        /**
         * @var scalar $value
         */
        foreach ($conditions as $column => $value) {
            if ('' !== $wheres) {
                $wheres .= ' AND ';
            }

            if (is_float($value)) {
                $wheres .= sprintf('`%s`', $column) . ' = %f';
            }

            if (is_int($value)) {
                $wheres .= sprintf('`%s`', $column) . ' = %d';
            }

            if (is_string($value)) {
                $wheres .= sprintf('`%s`', $column) . ' = %s';
            }

            $values[] = $value;
        }

        return [$wheres, $values];
    }
}
