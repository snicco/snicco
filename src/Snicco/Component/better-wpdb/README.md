# BetterWPDB - Keeps you safe and sane when working with custom tables in WordPress.

[![codecov](https://img.shields.io/badge/Coverage-100%25-success
)](https://codecov.io/gh/sniccowp/sniccowp)
[![Psalm Type-Coverage](https://shepherd.dev/github/sniccowp/sniccowp/coverage.svg?)](https://shepherd.dev/github/sniccowp/sniccowp)
[![Psalm level](https://shepherd.dev/github/sniccowp/sniccowp/level.svg?)](https://psalm.dev/)

BetterWPDB is a small class with zero dependencies that uses the default mysql connection created by WordPress.

## Table of contents

1. [Why you should use this](#why-you-should-use-this)
    1. [wpdb uses no prepared statements](#wpdb-does-not-use-prepared-statements)
    2. [wpdb has horrible error handling](#wpdb-has-horrible-error-handling)
    3. [wpdb is "slow"](#wpdb-is-slow)
    4. [wpdb is verbose](#wpdb-is-verbose-easy-to-misuse-and-hard-to-debug)
    5. [wpdb returns everything as strings](#wpdb-returns-everything-as-strings)
    6. [static analyzers don't like wpdb](#static-analysers-like-psalm-and-phpstan-have-trouble-understanding-wpdb)
2. [Installing](#installing)
    1. [composer](#composer)
    2. [setup](#setup)
3. [Running prepared queries](#running-prepared-queries)
4. [Selects](#select)
    1. [select](#select)
    2. [selectAll](#selectAll)
    3. [selectRow](#selectRow)
    4. [selectValue](#selectvalue)
    5. [selectLazy](#selectlazy)
    6. [exists](#exists)
5. [Inserts](#inserts)
    1. [insert](#insert)
    2. [bulkInsert](#bulkinsert)
6. [Updates](#updates)
    1. [update](#update)
    2. [update by primary key](#updatebyprimary)
7. [Deletes](#deletes)
8. [Transactions](#transactions)
9. [Logging](#logging)

## Why you should use this

The motivation for this library is best explained with simple examples. Let's assume we have the following custom table
in your database.

````mysql
'CREATE TABLE IF NOT EXISTS `test_table` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `test_string` varchar(10) COLLATE utf8mb4_unicode_520_ci UNIQUE NOT NULL,
  `test_float` FLOAT(9,2) UNSIGNED DEFAULT NULL,
  `test_int` INTEGER UNSIGNED DEFAULT NULL,
  `test_bool` BOOLEAN DEFAULT FALSE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;', []
);
````

- A unique string with max 10 chars
- A float column
- An unsigned integer column
- A boolean column

### wpdb does not use prepared statements

Besides what `wpdb::prepare()` has you thinking, wpdb is **NOT** using prepared statements. Explaining the differences
is beyond the scope of this README but as a recap:

When using prepared statements, the sql query and the actual values are sent separately to your database. It's thus
impossible to perform any SQL injection.

`wpdb::prepare()` is a [string escaper](https://github.com/WordPress/WordPress/blob/master/wp-includes/wp-db.php#L1395).
The name is misleading and wrong.

You can read more about this topic and why it's so important to use real prepared statements here:

- [Disclosure: WordPress WPDB SQL Injection - Technical](https://blog.ircmaxell.com/2017/10/disclosure-wordpress-wpdb-sql-injection-technical.html#The-Correct-Fix)
- [The Hitchhiker's Guide to SQL Injection prevention](https://phpdelusions.net/sql_injection)
- [On the (in)security of popular open source Content Management Systems](https://paragonie.com/blog/2016/08/on-insecurity-popular-open-source-php-cms-platforms#wordpress-prepared-statements)
- [Preventing SQL Injection in PHP Applications](https://paragonie.com/blog/2015/05/preventing-sql-injection-in-php-applications-easy-and-definitive-guide)

```php
❌ // This is not a prepared query

$wpdb->get_results(
    $wpdb->prepare('select * from `test_table` where `test_int` = %d and `test_string` = %s', [1, 'foo'])
);


✅ // This is a "real" prepared query

$better_wpdb->preparedQuery('select * from `wp_users` where `id` = ?' and `test_string` = ?, [1, 'foo']);
```

### wpdb has horrible error handling

The error handling in wpdb is pretty much non-existent. And if wpdb fails it does so gracefully. There is however now
way to recover from a database error as your application is in unknown state, so you want your database layer
to [fail loud and hard.](https://phpdelusions.net/articles/error_reporting)

1. **Lets compare error handling for totally malformed SQL.**

   wpdb will return (bool) false for failed queries which causes you to type-check the result or every single sql query
   only to (hopefully) throw an exception afterwards.

```php
❌ // This is what you typically see in WordPress code

$result = $wpdb->query('apparently not so valid sql');

if($result === false) {
    throw new Exception($wpdb->last_error);
}
```

```php
✅ // This is how it should be

$result = $better_wpdb->preparedQuery('apparently not so valid sql');

// You will never ever get here.

var_dump($e->getMessage()) // You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'apparently not a valid SQL statement' at line 1
                           // Query: [apparently not a valid SQL statement]
                           // Bindings: ['calvin@web.de', 1]

```

2. **Inserting data that is too big for the defined column.**

   In our db definition we did set `test_string` to a varchar(10).

```php
❌ 

$result = $wpdb->insert('test_table', [
    // the limit is 10, we insert 11 chars
    'test_string' => str_repeat('X', 11)
])

var_dump($result) // (bool) false
var_dump($wpdb->last_error); // WordPress database error: Processing the value for the following field failed: test_string.
                             // The supplied value may be too long or contains invalid data.

// Notice that there is no mention of the invalid query or what type of data was inserted
// As a little benefit you also have nothing in your error log.
```

```php
✅ 

$result = $better_wpdb->insert('test_table', [
     'test_string' => str_repeat('X', 11)
])

// You will never ever get here.

var_dump($e->getMessage()) // Data too long for column 'test_string' at row 1
                           // Query: [insert into test_table (test_string) values(?)]
                           // Bindings: ['XXXXXXXXXXX']

// This exception message is automatically logged.
```

3. **Inserting flat-out wrong data**

   In our test table we did set `test_int` to an unsigned integer. Let's see what happens if we try to insert a negative
   number.

```php
❌ 

$result = $wpdb->insert('test_table', [
    'test_string' => 'bar'
    'test_int' => -10
])

var_dump($result) // (bool) true
var_dump($wpdb->last_error); // ''

// Congratulations. Your database now contains invalid data and you will never know about it.
```

```php
✅ 

$result = $better_wpdb->insert('test_table', [
     'test_string' => 'bar'
     'test_int' => -10
])

// You will never ever get here.

var_dump($e->getMessage()) // Out of range value for column 'test_int' at row 1
                           // Query: [insert into test_table (test_string, test_int) values (?,?)]
                           // Bindings: ['bar', -10]

// This exception message is automatically logged.
```

4. **wpdb can only print errors has html and can only log to the configured `error_log` destination**

   If wpdb manages to catch a totally wrong db error (and you have "show_errors" turned on) wpdb will
   just [`echo` the output as html](https://github.com/WordPress/wordpress-develop/blob/5.9/src/wp-includes/wp-db.php#L1608) (
   very usefully during unit tests and rest api calls). Error logging
   is [hardcoded](https://github.com/WordPress/wordpress-develop/blob/5.9/src/wp-includes/wp-db.php#L1582), good luck
   sending db errors to sentry, new relic or using any psr-logger.

### wpdb is "slow"

This ties in directly to the graceful error handling.

❌ Before **every single** query wpdb will check the query against the table/column charset and collation. wpdb will also
validate data for write operations against the data you provided by fetching the full table info. If a query is deemed
not compatible `(bool) false`
is returned, and you will never now about it.

✅ Just set the charset and collation once for connection and let mysql handle what it can already handle.

### wpdb is verbose, easy to misuse and hard to debug.

The API of wpdb is needlessly verbose. Furthermore, It's hard to use correctly
and [easy to use wrong](https://wordpress.stackexchange.com/search?q=prepare).

The amount of code in WordPress plugins that looks something like this is shocking.

```php
❌  

$where = "WHERE foo = '" . esc_sql($_GET['data']) . "'";
$query = $wpdb->prepare("SELECT * FROM something $where LIMIT %d, %d", 1, 2);
$result = $wpdb->get_results($query);

✅ 

$result = $better_wpdb->selectAll('select * from something where foo = ? LIMIT ?', [1, 2]);

```

If you don't know why this is bad stop here
and [read this article by PHP core contributor Anthony Ferrara](https://blog.ircmaxell.com/2017/10/disclosure-wordpress-wpdb-sql-injection-technical.html)
.

> "The current system is insecure-by-design. That doesn’t mean it’s always hackable, but it means you have to actively work to make it not attackable.
> It’s better to switch to a design that’s secure-by-default and make the insecure the exceptional case."

### wpdb returns everything as strings

```php
$wpdb->insert('test_table', [
    'test_string' => 'foo',
    'test_int' => 10,
    'test_float' => 20.50,
    'test_bool' => true
])

❌  

$row = $wpdb->get_row($wpdb->prepare('select * from test_table where test_string = %s', 'foo'));

var_dump($row['test_string']); // (string) foo
var_dump($row['test_int']); // (string) 1
var_dump($row['test_float']); // (string) 20.50
var_dump($row['test_bool']); // (string) 1

✅ 

$row = $better_wpdb->selectRow('select * from test_table where test_string = ?', 'foo');

var_dump($row['test_string']); // (string) foo
var_dump($row['test_int']); // (int) 1
var_dump($row['test_float']); // (float) 20.50
var_dump($row['test_bool']); // (int) 1

```

### static analysers like psalm and phpstan have trouble understanding wpdb.

This ties into the error handling where different values are returned based on failure or success. Let's compare the
return signature of wpdb and better_wpdb:

```php
❌ // The abbreviated phpdoc of wpdb::get_row
   // This method has 4 different return types?

/**
*
* @param string|null $query 
* @param string $output 
* @param int $y 
* @return array|object|null|void Database query result in format specified by $output or null on failure.
*/
public function get_row($query = null, $output = OBJECT, $y = 0) {
   //
 }

✅  // Your favorite static analysis tool will thank you.

 /**
 * @param non-empty-string $sql
 * @param array<scalar|null> $bindings
 *
 * @return array<string, string|int|float|null>
 *
 * @throws NoMatchingRowFound
 * @throws QueryException
 * @throws InvalidArgumentException
 */
 public function selectRow(string $sql, array $bindings): array {
    //
 }
```

## Installing

You can install BetterWPDB via composer. The only requirement is `php: ^7.4|^8.0`. There are no further dependencies.

### composer

````shell
composer require sniccowp/betterwpdb
````

### setup

BetterWPDB **DOES NOT** open a second connection to your database. All you have to do to start using it is the
following:

```php
// require composer autoloader

use Snicco\Component\BetterWPDB\BetterWPDB;

$better_wpdb = BetterWPDB::fromWpdb();
```

Optionally you can also pass an already connected mysqli instance (in case you are connecting to a secondary database
etc.)

```php
// require composer autoloader

use Snicco\Component\BetterWPDB\BetterWPDB;

$mysqli = /* ... */

$better_wpdb = new BetterWPDB($mysqli);
```

## Running prepared queries

If you need full control of your sql query or have a complex use case you can directly use the low-level `preparedQuery`
method. For most use cases there are more high level methods available.

```php

use Snicco\Component\BetterWPDB\BetterWPDB;

$mysqli = /* ... */

$better_wpdb = new BetterWPDB($mysqli);

// stmt is an instance of mysqli_stmt
$stmt = $better_wpdb->preparedQuery('select * from test_table where test_string = ? or test_int = ?', ['foo', 1]);

var_dump($stmt->num_rows);
var_dump($stmt->affected_rows);
```

❌ Never pass **ANY** user input into the first argument of `preparedQuery`

✅ Use "?" placeholders for user input and pass in an array of values.

❌ Never allow users to provide table names, column names, order by values or similar

```php
❌❌❌ // NEVER EVER DO THIS. You will get hacked.

$order_by = $_GET['order'];

$better_wpdb->preparedQuery(
   'select * from test_table where test_string = ? order by ?', 
   [$_GET['test_string'], $order_by]
)

✅ // Use a whitelist approach

$order_by = 'desc';
$_get = strtolower($_GET['order_by']);

if('asc' === $order_by) {
    $order_by = 'asc';
}

$better_wpdb->preparedQuery(
   'select * from test_table where test_string = ? order by ?', 
   [$_GET['test_string'], $order_by]
)


```

If you follow these three simply rules you are 100% safe from any sql-injections.

## Selects

### select

The most low-level select method. Returns an instance of `mysqli_result`

```php

/** @var mysqli_result $result */
$result = $better_wpdb->select('select * from test_table where test_string = ?', ['foo']);

echo $result->num_rows

while($row = $result->fetch_array()) {
    // Do stuff with $row
}
```

### selectAll

Returns an array or all matching records.

This method is preferred for smaller result sets. If you need to query a lot of rows using [selectLazy](#selectlazy) is
preferred.

```php

/** @var array<array> $result */
$rows = $better_wpdb->selectAll('select * from test_table where test_string = ?', ['foo']);

foreach ($rows as $row) {
   echo $row['test_string'];
   echo $row['test_int'];
   echo $row['test_bool'];
   echo $row['test_float'];
}
```

### selectLazy

Occasionally you will need to query a lot of records from your database to process them in some form. A typical use-case
would be exporting 100k orders into a CSV file. If you try to use `selectAll` for this you will be out of memory
immediately.

This is where the `selectLazy` method is extremely useful. It returns
a [PHP Generator](https://www.php.net/manual/en/language.generators.syntax.php) that has always only 1 row in memory.

```php
❌ // you just loaded 100k rows into memory

$orders = $better_wpdb->selectAll('select * from orders where created_at <= ?', [$date]);

✅ // You load 1 row at a time. But only when you start looping over the result.

/** @var Generator $orders */
$orders = $better_wpdb->selectLazy('select * from orders where created_at <= ?', [$date]);

// You have not made any db queries yet.

foreach ($orders as $order) {
    // One order is fetched at a time.
    // You only make one db query. But thanks to the generator you only have one order in memory
    
    // process order
}

```

### selectRow

Returns the first row that matches the provided query. Throws an exception if no row can be found.

```php
try {

    /** @var array $row */
    $row = $better_wpdb->selectRow('select * from test_table where test_string = ? limit 1', ['foo']);
    
    echo $row['test_string'];
    echo $row['test_int'];
    echo $row['test_bool'];
    echo $row['test_float'];
    
}catch (NoMatchingRowFound $e) {
    // don't catch this exception. Just a demo.
}
```

### selectValue

Selects a single value from or throws an exception if no rows are found.

```php
try {

    /** @var int $row */
    $count = $better_wpdb->selectValue('select count(*) from test_table where test_string = ?', ['foo']);
    
}catch (NoMatchingRowFound $e) {
    // don't catch this exception. Just a demo.
}
```

### exists

You can use this method to check if a record exists in the database

```php
/** @var bool $exists */
$exists = $better_wpdb->exists('test_table', [
   'test_string' => 'foo', 
   'test_float' => null, 
   'test_int' => 1
   ])
```

❌ Never allow user input as keys for the array.

## Inserts

### insert

Inserts a single row into the database and returns the id (if auto-incrementing primary keys are used).

```php
/** @var int $id */
$id = $better_wpdb->insert('test_table', [
    'test_string' => 'foo',
    'test_int' => 10
]);
```

❌ Never allow user input as keys for the array.

### bulkInsert

A common use case is inserting multiple records at once and ensuring that either all records are inserted or none.

Think importing a csv of members into your database. You don't want 5 inserts to fail and 5 to succeed. This method
helps you achieve this. All inserts will be performed inside a database transaction that will automatically commit on
success or roll back if any errors happen.

```php
$result = $better_wpdb->bulkInsert('test_table', [
  ['test_string' => 'foo', 'test_float' => 10.00, 'test_int' => 1],
  ['test_string' => 'bar', 'test_float' => 20.00, 'test_int' => 2, ],
]);

var_dump($result); // (integer) 2

// This will fail since test_int can not be negative. No rows will be inserted

$result = $better_wpdb->bulkInsert('test_table', [
  ['test_string' => 'foo1', 'test_int' => 1],
  
  /* .. */ 
  
  ['test_string' => 'foo999', 'test_int' => 999],
  
  // This will throw an exception and everything will automatically roll back.
  ['test_string' => 'foo1000', 'test_int' => -1000],
]);
```

❌ Never allow user input as keys for the array.

You can pass any iterable into `bulkInsert`.

This is how you import a huge CSV file into your database without running out of memory.

````php
// please don't copy-paste this code. It's just an example.

$read_csv = function() :Generator{

   $file = fopen('/path/to/hugh/csv/orders.csv')
   
   while(!feof($file)) {
  
    $row = fgetcsv($file, 4096);
    yield $row
   }
}

$importer_rows_count = $better_wpdb->bulkInsert('orders', $read_csv());

var_dump($importer_rows_count); // 100000

````

## Updates

### updateByPrimary

Updates a record by its primary key. By default, it will be assumed that the primary key column name is `id`.

```php
 /** @var int $affected_rows */
 $affected_rows = $better_wpdb->updateByPrimary('test_table', 1, [
            'test_string' => 'bar',
            'test_int' => 20,
 ]);

 // Use a custom column name
 $affected_rows = $better_wpdb->updateByPrimary('test_table', ['my_id' => 1] , [
            'test_string' => 'bar',
            'test_int' => 20,
 ]);
```

❌ Never allow user input as keys for the array.

### update

A generic update method. The second argument is an array of conditions, the third argument an array of changes.

```php
 /** @var int $affected_rows */
 $affected_rows = $better_wpdb->update('test_table',
            ['test_int' => 10], // conditions
            ['test_bool' => true] // changes
        );
```

❌ Never allow user input as keys for the conditions

❌ Never allow user input as keys for the changes

## Deletes

### delete

Deletes all records that match the provided conditions.

```php
 /** @var int $deleted_rows */
 $deleted_rows = $better_wpdb->delete('test_table', ['test_string' => 'foo']);
```

❌ Never allow user input as keys for the conditions

## Transactions

Unfortunately, database transactions are used very rarely in WordPress plugins. A transaction ensures that either all or
db queries inside the transaction succeed or all fail.

Typical code you find in many WordPress plugins:

```php
❌ // This is awful. What happens if a customer and order is created but creating the payment fails?

 my_plugin_create_customer();
 my_plugin_create_create();
 my_plugin_create_payment();

✅ // wrap these calls inside a database transaction

$better_wpdb->transactional(function () {

    my_plugin_create_customer();
    my_plugin_create_create(); 
    my_plugin_create_payment(); // If this fails, customer and order will not be created.
 
});

```

## Logging

You can a second argument to the constructor of BetterWPDB.

Implement the
simple [QueryLogger](https://github.com/sniccowp/sniccowp/blob/feature/better_wpdb/src/Snicco/Component/better-wpdb/src/QueryLogger.php)
interface and start logging your database queries to your favorite profiling service.

The following is pseudocode to log to new relic:

````php
<?php

use Snicco\Component\BetterWPDB\BetterWPDB;use Snicco\Component\BetterWPDB\QueryInfo;use Snicco\Component\BetterWPDB\QueryLogger;

class NewRelicLogger implements QueryLogger {
    
     public function log(QueryInfo $info) :void {
         
         $sql = $info->sql;
         $duration = $info->duration_in_ms;
         $start_time = $info->start;
         $end_time = $info->end
         
         // log to new relic
         
     }   
}

$better_wpdb = BetterWPDB::fromWpdb(new NewRelicLogger());

// Now, all queries, including the sql and duration are logged automatically
$better_wpdb->insert('test_table' , ['test_string' => 'foo']);

````

