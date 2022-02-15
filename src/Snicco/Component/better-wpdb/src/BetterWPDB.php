<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB;

use Closure;
use Generator;
use InvalidArgumentException;
use LogicException;
use mysqli;
use mysqli_result;
use mysqli_stmt;
use ReflectionException;
use RuntimeException;
use Throwable;

use function array_keys;
use function array_map;
use function array_values;
use function count;
use function implode;
use function is_array;
use function is_bool;
use function is_double;
use function is_int;
use function is_null;
use function is_scalar;
use function is_string;
use function microtime;
use function mysqli_report;
use function rtrim;
use function sprintf;
use function str_repeat;
use function strtr;

use const MYSQLI_ASSOC;
use const MYSQLI_OPT_INT_AND_FLOAT_NATIVE;

final class BetterWPDB
{

    private mysqli $mysqli;
    private ?string $original_sql_mode = null;
    private bool $in_transaction = false;
    private bool $is_handling_errors = false;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * @throws ReflectionException
     */
    public static function fromWpdb(): self
    {
        return new self(MysqliFactory::fromWpdbConnection());
    }

    /**
     * @param array<scalar|null> $bindings
     */
    public function preparedQuery(string $sql, array $bindings = []): mysqli_stmt
    {
        return $this->runWithErrorHandling(function () use ($sql, $bindings): mysqli_stmt {
            $bindings = $this->convertBindings($bindings);
            $stmt = $this->createPreparedStatement($sql, $bindings);
            $start = microtime(true);
            $stmt->execute();
            $end = microtime(true);
            $this->log(new QueryInfo($start, $end, $sql, $bindings));

            return $stmt;
        });
    }

    /**
     * Runs the callback inside a transaction that
     * automatically commits on success and rolls back if any errors happen.
     *
     * @template T
     * @param Closure($this):T $callback
     * @return T
     */
    public function transactional(Closure $callback)
    {
        if ($this->in_transaction) {
            throw new LogicException('Nested transactions are currently not supported.');
        }

        return $this->runWithErrorHandling(function () use ($callback) {
            try {
                $this->in_transaction = true;
                $this->mysqli->begin_transaction();
                $res = $callback($this);
                $this->mysqli->commit();
                $this->in_transaction = false;
                return $res;
            } catch (Throwable $e) {
                $this->mysqli->rollback();
                $this->in_transaction = false;
                throw $e;
            }
        });
    }

    /**
     * @param non-empty-string $table
     * @param int|non-empty-string|non-empty-array<non-empty-string, int|non-empty-string> $primary_key
     * @param non-empty-array<non-empty-string, scalar|null> $changes !!! IMPORTANT !!!
     *                                                               Keys of $data MUST never be user provided.
     */
    public function updateByPrimaryKey(string $table, $primary_key, array $changes): mysqli_stmt
    {
        $primary_key = is_array($primary_key) ? $primary_key : ['id' => $primary_key];

        return $this->preparedUpdate($table, $primary_key, $changes);
    }

    /**
     * @param non-empty-string $table
     * @param non-empty-array<non-empty-string, scalar|null> $conditions
     * @param non-empty-array<non-empty-string, scalar|null> $changes !!! IMPORTANT !!!
     *                                                               Keys of $data MUST never be user provided.
     */
    public function preparedUpdate(string $table, array $conditions, array $changes): mysqli_stmt
    {
        $this->validateTableName($table);
        $this->validateProvidedColumnNames(array_keys($conditions));
        $this->validateProvidedColumnNames(array_keys($changes));

        $table = $this->escIdentifier($table);
        $sql = "update $table set ";

        $updates = [];
        $wheres = [];
        $bindings = [];

        foreach ($changes as $col_name => $value) {
            $updates[] = $this->escIdentifier($col_name) . ' = ?';
            $bindings[] = $value;
        }

        foreach ($conditions as $col_name => $value) {
            $col_name = $this->escIdentifier($col_name);
            if (is_null($value)) {
                $wheres[] = "$col_name is null";
            } else {
                $wheres[] = "$col_name = ? ";
                $bindings[] = $value;
            }
        }

        $sql .= implode(', ', $updates);
        $sql .= ' where ' . implode(' and ', $wheres);

        return $this->preparedQuery($sql, $bindings);
    }

    /**
     * @param array<scalar|null> $bindings
     */
    public function preparedSelect(string $sql, array $bindings): mysqli_result
    {
        return $this->preparedQuery($sql, $bindings)->get_result();
    }

    /**
     * Returns the entire result set as associative array.
     * This method is preferred for small result sets.
     * For large result sets this method will cause memory issues, and it's better to use {@see BetterWPDB::preparedSelectAll()}
     *
     * @param array<scalar|null> $bindings
     * @return list<array<string, string|int|float|bool|null>>
     */
    public function preparedSelectAll(string $sql, array $bindings): array
    {
        /** @var list<array<string, string|int|float|bool|null>> $val */
        $val = $this->preparedSelect($sql, $bindings)->fetch_all(MYSQLI_ASSOC);
        return $val;
    }

    /**
     * This method should be used if you want to iterate over a big number of records.
     *
     * @param array<int,scalar|null> $bindings
     * @return Generator<array<string,string|int|float|bool|null>>
     */
    public function preparedSelectLazy(string $sql, array $bindings): Generator
    {
        $res = $this->preparedSelect($sql, $bindings);

        while ($row = $res->fetch_assoc()) {
            yield $row;
        }
    }

    /**
     * @param non-empty-string $table
     * @param non-empty-array<non-empty-string, scalar|null> $data !!! IMPORTANT !!!
     *                                                             Keys of $data MUST never be user provided.
     */
    public function preparedInsert(string $table, array $data): mysqli_stmt
    {
        $this->validateTableName($table);

        $column_names = array_keys($data);
        $this->validateProvidedColumnNames($column_names);

        $sql = $this->buildInsertSql($table, $column_names);
        return $this->preparedQuery($sql, array_values($data));
    }

    /**
     * Runs a bulk insert of records in a transaction.
     * If any record can't be inserted the entire transaction will be rolled back.
     *
     * @param non-empty-string $table
     * @param iterable<array<non-empty-string,scalar|null>> $records !!! IMPORTANT !!!
     *                                                               Keys of $data MUST never be user provided.
     * @return int The number of inserted records
     */
    public function preparedBulkInsert(string $table, iterable $records): int
    {
        $this->validateTableName($table);

        return $this->transactional(function () use ($table, $records): int {
            $stmt = null;
            $sql = null;
            $expected_types = null;
            $inserted = 0;

            foreach ($records as $record) {
                if (empty($record)) {
                    throw new InvalidArgumentException('Each record has to be a non-empty-array.');
                }
                // only create the insert sql once.
                $sql = is_null($sql)
                    ? $this->buildInsertSql($table, array_keys($record))
                    : $sql;

                // only create one prepared statement
                $stmt = is_null($stmt)
                    ? $this->mysqli->prepare($sql)
                    : $stmt;

                $bindings = $this->convertBindings($record);

                // Retrieve the expected types from the first record.
                if (is_null($expected_types)) {
                    $expected_types = (string)$this->paramTypes($bindings);
                }

                $record_types = (string)$this->paramTypes($bindings);
                if ($expected_types !== $record_types) {
                    throw new InvalidArgumentException(
                        sprintf(
                            "Records are not of consistent type.\nExpected: [%s] and got [%s].",
                            rtrim(strtr($expected_types, ['s' => 'string,', 'd' => 'double,', 'i' => 'integer,']), ','),
                            rtrim(strtr($record_types, ['s' => 'string,', 'd' => 'double,', 'i' => 'integer,']), ','),
                        )
                    );
                }
                $stmt->bind_param($record_types, ...$bindings);
                $stmt->execute();

                $inserted = $inserted + $stmt->affected_rows;
            }

            return $inserted;
        });
    }

    /**
     * @template T
     * @param Closure():T $run_query
     * @return T
     */
    public function runWithErrorHandling(Closure $run_query)
    {
        if ($this->is_handling_errors) {
            return $run_query();
        }

        if (!isset($this->original_sql_mode)) {
            $this->queryOriginalSqlMode();
        }

        // Turn on error reporting
        $this->mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $res = $this->mysqli->query("SET SESSION sql_mode='TRADITIONAL'");
        if (true !== $res) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Could not set mysql error reporting to traditional.');
            // @codeCoverageIgnoreEnd
        }

        $this->is_handling_errors = true;

        try {
            return $run_query();
        } finally {
            // Turn back to previous error reporting so that shitty wpdb doesn't break.
            $this->mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 0);
            mysqli_report(MYSQLI_REPORT_OFF);
            $this->mysqli->query("SET SESSION sql_mode='$this->original_sql_mode'");
            $this->is_handling_errors = false;
        }
    }

    private function queryOriginalSqlMode(): void
    {
        $stmt = $this->mysqli->query('SELECT @@SESSION.sql_mode');
        if (!$stmt instanceof mysqli_result) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Could not determine current mysqli mode.');
            // @codeCoverageIgnoreEnd
        }
        $res = $stmt->fetch_row();
        if (!is_array($res) || !isset($res[0]) || !is_string($res[0])) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Could not determine current mysqli mode.');
            // @codeCoverageIgnoreEnd
        }
        $this->original_sql_mode = $res[0];
    }

    /**
     * @param non-empty-string $table
     * @param non-empty-string[] $column_names
     */
    private function buildInsertSql(string $table, array $column_names): string
    {
        $column_names = array_map(
            fn($column_name) => $this->escIdentifier($column_name),
            $column_names
        );
        $columns = implode(',', $column_names);
        $table = $this->escIdentifier($table);
        $placeholders = str_repeat('?,', count($column_names) - 1) . '?';

        return "insert into $table ($columns) values ($placeholders)";
    }

    /**
     * @param list<string|int|float|null> $bindings
     */
    private function createPreparedStatement(string $sql, array $bindings): mysqli_stmt
    {
        /** @var mysqli_stmt $stmt */
        $stmt = $this->mysqli->prepare($sql);

        $types = $this->paramTypes($bindings);

        if ($types) {
            $stmt->bind_param($types, ...$bindings);
        }
        return $stmt;
    }

    /**
     * @param non-empty-string $identifier
     */
    private function escIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * @param array<string|int|float|null> $bindings
     * @return string|null
     */
    private function paramTypes(array $bindings): ?string
    {
        $types = '';
        foreach ($bindings as $binding) {
            if (is_double($binding)) {
                $types .= 'd';
            } elseif (is_int($binding)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
        }
        return empty($types) ? null : $types;
    }

    private function log(QueryInfo $query_info): void
    {
        //
    }

    /**
     * @return list<string|int|float|null>
     */
    private function convertBindings(array $bindings): array
    {
        $b = [];

        foreach ($bindings as $binding) {
            if (!is_scalar($binding) && !is_null($binding)) {
                throw new InvalidArgumentException('All bindings have to be of type scalar or null.');
            }
            if (is_bool($binding)) {
                $binding = $binding ? 1 : 0;
            }
            $b[] = $binding;
        }
        return $b;
    }

    /**
     * @param string $table
     */
    private function validateTableName(string $table): void
    {
        if ('' === $table) {
            throw new InvalidArgumentException('A table name must be a non-empty-string.');
        }
    }

    private function validateProvidedColumnNames(array $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Column names can not be an empty array.');
        }
        foreach ($data as $name) {
            if (!is_string($name) || '' === $name) {
                throw new InvalidArgumentException('All column names must be a non-empty-strings.');
            }
        }
    }

}