<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Mysqli;

use Illuminate\Database\QueryException;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use mysqli_stmt;

use function count;
use function sprintf;

/**
 * This class needs to double-check every method call to mysqli for errors because by default
 * WordPress configures mysqli to not throw exceptions and also sets the mysql mode to non-strict.
 *
 * @psalm-internal Snicco\Component\Eloquent
 *
 * @interal
 */
final class MysqliDriver implements MysqliDriverInterface
{
    private mysqli $mysqli;

    private MysqliReconnect $reconnect;

    public function __construct(mysqli $mysqli, MysqliReconnect $reconnect)
    {
        $this->mysqli = $mysqli;
        $this->reconnect = $reconnect;
    }

    public function doSelect(string $sql, array $bindings): array
    {
        $stmt = $this->createPreparedStatement($sql, $bindings);

        $this->executeStatement($stmt, $sql, $bindings);

        $result = $this->getMysqliResult($stmt, $sql, $bindings);

        $values = [];

        while ($row = $result->fetch_object()) {
            $values[] = $row;
        }

        return $values;
    }

    public function doStatement(string $sql, array $bindings): bool
    {
        if (empty($bindings)) {
            try {
                $res = $this->mysqli->query($sql);

                if (false === $res) {
                    throw $this->lastException();
                }
            } catch (mysqli_sql_exception $e) {
                throw new QueryException($sql, $bindings, $e);
            }

            return true === $res;
        }

        $stmt = $this->createPreparedStatement($sql, $bindings);

        return $this->executeStatement($stmt, $sql, $bindings);
    }

    public function doUnprepared(string $sql): bool
    {
        try {
            $result = $this->mysqli->query($sql);

            if (false === $result) {
                throw $this->lastException();
            }
        } catch (mysqli_sql_exception $e) {
            throw new QueryException($sql, [], $e);
        }

        return true === $result;
    }

    public function doCursorSelect(string $sql, array $bindings): mysqli_result
    {
        $statement = $this->createPreparedStatement($sql, $bindings);

        $this->executeStatement($statement, $sql, $bindings);

        return $this->getMysqliResult($statement, $sql, $bindings);
    }

    public function lastInsertId(): int
    {
        return (int) $this->mysqli->insert_id;
    }

    public function isStillConnected(): bool
    {
        return $this->mysqli->ping();
    }

    public function reconnect(): bool
    {
        $this->mysqli = $this->reconnect->getMysqli();

        return true;
    }

    public function commit(): bool
    {
        try {
            $res = $this->mysqli->commit();

            if (false === $res) {
                throw $this->lastException();
            }

            return true;
        } catch (mysqli_sql_exception $e) {
            throw new QueryException('COMMIT', [], $e);
        }
    }

    public function beginTransaction(): bool
    {
        try {
            $res = $this->mysqli->begin_transaction();

            if (false === $res) {
                throw $this->lastException();
            }

            return true;
        } catch (mysqli_sql_exception $e) {
            throw new QueryException('START TRANSACTION', [], $e);
        }
    }

    public function exec(string $statement)
    {
        return $this->doAffectingStatement($statement, []);
    }

    public function doAffectingStatement(string $sql, array $bindings): int
    {
        if (empty($bindings)) {
            try {
                $res = $this->mysqli->query($sql);

                if (false === $res) {
                    throw $this->lastException();
                }

                return $this->mysqli->affected_rows;
            } catch (mysqli_sql_exception $e) {
                throw new QueryException($sql, $bindings, $e);
            }
        }

        $statement = $this->createPreparedStatement($sql, $bindings);
        $this->executeStatement($statement, $sql, $bindings);

        return $statement->affected_rows;
    }

    public function rollback(): bool
    {
        try {
            $res = $this->mysqli->rollback();

            if (false === $res) {
                throw $this->lastException();
            }

            return true;
        } catch (mysqli_sql_exception $e) {
            throw new QueryException('ROLLBACK', [], $e);
        }
    }

    /**
     * @throws QueryException
     * @psalm-suppress MixedAssignment
     */
    private function createPreparedStatement(string $sql, array $bindings = []): mysqli_stmt
    {
        try {
            $stmt = $this->mysqli->prepare($sql);
        } catch (mysqli_sql_exception $e) {
            throw new QueryException($sql, $bindings, $e);
        }

        if (! $stmt instanceof mysqli_stmt) {
            throw new QueryException($sql, $bindings, $this->lastException());
        }

        if (! count($bindings)) {
            return $stmt;
        }

        $types = '';

        foreach ($bindings as $binding) {
            if (is_float($binding)) {
                $types .= 'd';
            } elseif (is_int($binding)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
        }

        $copy = $bindings;

        try {
            $success = $stmt->bind_param($types, ...$bindings);
        } catch (mysqli_sql_exception $e) {
            throw new QueryException(
                $sql,
                $copy,
                $this->lastException()
            );
        }

        if (! $success) {
            throw new QueryException(
                $sql,
                $copy,
                $this->lastException()
            );
        }

        return $stmt;
    }

    private function lastException(): mysqli_sql_exception
    {
        /** @var array<array{error: string, errno: string, sqlstate: string}> $errors */
        $errors = $this->mysqli->error_list;
        $msg = '';

        if (isset($errors[0])) {
            $msg = sprintf(
                "error: %s\nerrno: [%s]\nsqlstate: [%s]",
                $errors[0]['error'] ?? '',
                $errors[0]['errno'] ?? '',
                $errors[0]['sqlstate'] ?? '',
            );
        }

        return new mysqli_sql_exception($msg);
    }

    /**
     * @throws QueryException
     */
    private function executeStatement(mysqli_stmt $stmt, string $sql, array $bindings): bool
    {
        try {
            $success = $stmt->execute();

            if (false === $success) {
                throw $this->lastException();
            }
        } catch (mysqli_sql_exception $e) {
            throw new QueryException(
                $sql,
                $bindings,
                $e
            );
        }

        return $success;
    }

    /**
     * @throws QueryException
     */
    private function getMysqliResult(mysqli_stmt $stmt, string $sql, array $bindings): mysqli_result
    {
        try {
            $result = $stmt->get_result();
            if (! $result instanceof mysqli_result) {
                throw $this->lastException();
            }
        } catch (mysqli_sql_exception $e) {
            throw new QueryException($sql, $bindings, $e);
        }

        return $result;
    }
}
