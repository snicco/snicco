<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\KeysetPagination;

use InvalidArgumentException;

use function array_fill;
use function array_flip;
use function array_intersect_key;
use function array_key_first;
use function array_key_last;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_shift;
use function count;
use function str_replace;
use function strpos;
use function strtolower;
use function substr_count;

final class Query
{
    /**
     * @var string
     */
    public const SORT_ASC = 'asc';

    /**
     * @var string
     */
    public const SORT_DESC = 'desc';

    /**
     * @var non-empty-list<non-empty-string>
     */
    private array $sorting_column_names;

    /**
     * @var positive-int
     *
     * @readonly
     */
    private int $batch_size;

    /**
     * @var array<scalar|null>
     */
    private array $static_column_bindings;

    /**
     * @var array<string, 1|2>
     */
    private array $binding_count_per_column = [];

    private string $where;

    private string $order_by;

    /**
     * @var non-empty-string
     */
    private string $sql_first_batch;

    /**
     * @var non-empty-string
     */
    private string $sql_nth_batch;

    /**
     * @param string                                          $sql                           A select SQL query with optional where clauses at the end. The where clause should only use columns that have an index.
     * @param non-empty-array<non-empty-string, "asc"|"desc"> $deterministic_sorting_columns column names for sorting that ensure a deterministic sorting order
     * @param positive-int                                    $batch_size
     * @param scalar[]                                        $static_column_bindings        the values for "static" column values if the "$sql" query contains conditions
     */
    public function __construct(
        string $sql,
        array $deterministic_sorting_columns,
        int $batch_size = 500,
        array $static_column_bindings = []
    ) {
        if (substr_count($sql, '?') !== count($static_column_bindings)) {
            throw new InvalidArgumentException(
                'The placeholder count does not match the count of static column values.'
            );
        }

        $sql = strtolower($sql);
        $this->sorting_column_names = array_keys($deterministic_sorting_columns);
        $this->static_column_bindings = $static_column_bindings;
        $this->batch_size = $batch_size;

        $this->where = (false !== strpos($sql, 'where'))
            ? ' and '
            : ' where ';

        $this->order_by = ' order by ';

        $this->applyCursorQuery($deterministic_sorting_columns);

        $this->sql_first_batch = $sql . $this->order_by . ' limit ? ';
        $this->sql_nth_batch = $sql . $this->where . $this->order_by . ' limit ? ';
    }

    /**
     * @interal
     *
     * @param list<array<string,?scalar>> $batch
     */
    public function createResult(array $batch): ResultSet
    {
        if (empty($batch)) {
            return ResultSet::empty();
        }

        $has_more = (count($batch) === ($this->batch_size + 1));

        if ($has_more) {
            // We need to remove the last record because
            // we are fetching $batch_size + 1 records.
            // Otherwise, we will end up with duplicates.
            array_pop($batch);
        }

        $last_record = $batch[(int) array_key_last($batch)];
        $last_record_sorting_values = array_intersect_key(
            $last_record,
            array_flip($this->sorting_column_names)
        );

        return ResultSet::fromRecords(
            $batch,
            new LeftOff($last_record_sorting_values),
            ! $has_more
        );
    }

    /**
     * @interal
     *
     * @return array{0: non-empty-string, 1: array<scalar|null>}
     */
    public function buildPlaceholderSQLAndBindings(?LeftOff $left_off): array
    {
        if (null === $left_off) {
            return [
                $this->sql_first_batch,
                array_merge($this->static_column_bindings, [$this->batch_size + 1]),
            ];
        }

        $last_record = $left_off->last_included_record_sorting_values;
        $bindings = $this->static_column_bindings;

        /*
         * If the pagination query uses compound sorting columns all but the last "left off value"
         * need to be present two times in the bindings array. The last value
         * needs to be present once in the bindings array for mysqli.
         */
        foreach ($this->sorting_column_names as $column) {
            if (! isset($last_record[$column])) {
                throw new InvalidArgumentException(
                    "Sorting column [{$column}] is missing. Please check that your select statement includes the column [{$column}]."
                );
            }

            $bindings_per_column = array_fill(
                0,
                $this->binding_count_per_column[$column],
                $last_record[$column]
            );

            $bindings = array_merge($bindings, $bindings_per_column);
        }

        $bindings[] = ($this->batch_size + 1);

        return [$this->sql_nth_batch, $bindings];
    }

    /**
     * A helper method that will recursively build out the necessary where
     * clauses and order by clauses.
     *
     * A given input for sorting columns ['a' => 'asc', 'b' => 'asc'] will must
     * produce the following SQL:
     *
     * where `a` > ? or ( `a` = ? and `b` > ? ) order by `a` asc, `b` asc
     *
     * For `a` sorting order ['a' => 'desc', 'b' => 'asc'] it will produce:
     *
     * where `a` < ? or ( `a` = ? or `b` > ? ) order by `a` desc, `b` asc
     *
     * This allows MySQL to fully utilize the index on the primary sorting
     * column.
     *
     * @param non-empty-array<non-empty-string, 'asc'|'desc'> $sorting_columns
     *
     * @see https://stackoverflow.com/questions/38017054/mysql-cursor-based-pagination-with-multiple-columns
     * @see http://mysql.rjweb.org/doc.php/deletebig#iterating_through_a_compound_key
     * @see http://mysql.rjweb.org/doc.php/pagination
     */
    private function applyCursorQuery(array $sorting_columns): void
    {
        $order_direction_to_sql_sign = static fn (string $order): string => 'desc' === $order ? '<' : '>';

        $column_name = array_key_first($sorting_columns);

        $escaped_column_name = $this->escIdentifier($column_name);

        $sorting_direction = $sorting_columns[$column_name];

        $direction_sign = $order_direction_to_sql_sign($sorting_direction);

        array_shift($sorting_columns);

        $is_last = empty($sorting_columns);

        if ($is_last) {
            $this->binding_count_per_column[$column_name] = 1;
            $this->order_by .= "{$escaped_column_name} {$sorting_direction}";
            $this->where .= " {$escaped_column_name} {$direction_sign} ?";

            return;
        }

        /*
         * We have multiple sorting columns.
         * This means that the current column that is being processed
         * will appear twice in the final SQL statement which
         * means we will need the according cursor value also twice
         * in the prepared statement bindings.
         *
         * The order by part needs a ", " appended because the next
         * iteration will have another order by value.
         *
         * To finish the where clause we need to call this function
         * recursively as long as we have more than one column left.
         *
         */
        $this->binding_count_per_column[$column_name] = 2;
        $this->order_by .= "{$escaped_column_name} {$sorting_direction}, ";
        $this->where .= " {$escaped_column_name} {$direction_sign} ? or ( {$escaped_column_name} = ? and ";

        /** @var non-empty-array<non-empty-string, 'asc'|'desc'> $sorting_columns */
        $this->applyCursorQuery(
            $sorting_columns,
        );

        // We need to close out the or statement here.
        $this->where .= ' )';
    }

    /**
     * @param non-empty-string $identifier
     */
    private function escIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
