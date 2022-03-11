<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Illuminate\Database\QueryException;
use mysqli_sql_exception;
use Snicco\Component\Eloquent\Illuminate\MysqliConnection;
use Snicco\Component\Eloquent\Mysqli\MysqliFactory;

use function mysqli_report;

use const MYSQLI_REPORT_OFF;

/**
 * @internal
 */
final class MysqliConnectionErrorHandlingTest extends WPTestCase
{
    private MysqliConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = (new MysqliFactory())->create();
        // This is the default behaviour in WordPress whether we like it or not.
        // need to set this explicitly because in PHP8.1 is enabled by default
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    /**
     * @test
     */
    public function errors_get_handled_for_inserts(): void
    {
        try {
            $this->connection->insert('foo', ['bar']);
            $this->fail('No exception thrown when inserting a value that is to big for a column');
        } catch (QueryException $e) {
            $this->assertStringContainsString('error: You have an error in your SQL syntax', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function errors_get_handles_for_updates(): void
    {
        try {
            $this->connection->update('foobar', [
                'foo' => 'bar',
            ]);
            $this->fail('No query exception thrown');
        } catch (QueryException $e) {
            $this->assertStringStartsWith('error: You have an error in your SQL syntax', $e->getMessage());
            $this->assertSame('foobar', $e->getSql());
            $this->assertSame([
                'foo' => 'bar',
            ], $e->getBindings());
        }
    }

    /**
     * @test
     */
    public function errors_get_handled_for_deletes(): void
    {
        try {
            $this->connection->delete('foobar', [
                'foo' => 'bar',
            ]);
            $this->fail('No query exception thrown');
        } catch (QueryException $e) {
            $this->assertStringStartsWith('error: You have an error in your SQL syntax', $e->getMessage());
            $this->assertSame('foobar', $e->getSql());
            $this->assertSame([
                'foo' => 'bar',
            ], $e->getBindings());
        }
    }

    /**
     * @test
     */
    public function errors_get_handled_for_unprepared_queries(): void
    {
        try {
            $this->connection->unprepared('foobar');
            $this->fail('No query exception thrown');
        } catch (QueryException $e) {
            $this->assertStringStartsWith('error: You have an error in your SQL syntax', $e->getMessage());
            $this->assertSame('foobar', $e->getSql());
            $this->assertSame([], $e->getBindings());
        }
    }

    /**
     * @test
     */
    public function errors_get_handled_for_cursor_selects(): void
    {
        try {
            $generator = $this->connection->cursor('foobar', [
                'foo' => 'bar',
            ]);

            foreach ($generator as $foo) {
                $this->fail('No Exception thrown');
            }
        } catch (QueryException $e) {
            $this->assertInstanceOf(mysqli_sql_exception::class, $e->getPrevious());
            $this->assertSame('foobar', $e->getSql());
            $this->assertSame([
                'foo' => 'bar',
            ], $e->getBindings());
        }
    }

    /**
     * @test
     */
    public function errors_get_handled_for_selects(): void
    {
        try {
            $this->connection->select('foobar', [
                'foo' => 'bar',
            ]);
            $this->fail('no exception thrown');
        } catch (QueryException $e) {
            $this->assertStringStartsWith('error: You have an error in your SQL syntax', $e->getMessage());
            $this->assertSame('foobar', $e->getSql());
            $this->assertSame([
                'foo' => 'bar',
            ], $e->getBindings());
        }
    }
}
