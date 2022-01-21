<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\wordpress;

use mysqli;
use Mockery as m;
use mysqli_stmt;
use mysqli_result;
use Codeception\TestCase\WPTestCase;
use Snicco\Component\Eloquent\Mysqli\MysqliDriver;
use Snicco\Component\Eloquent\Mysqli\MysqliReconnect;

final class MysqliDriverTest extends WPTestCase
{
    
    /** @test */
    public function the_mysqli_statement_gets_prepared_correctly()
    {
        $mysqli = m::mock(mysqli::class);
        $mysqli_driver = new MysqliDriver($mysqli, new MysqliReconnect(function () { }));
        
        $mysqli->shouldReceive('prepare')->andReturn(
            $statement = m::mock(mysqli_stmt::class)
        );
        
        $statement->shouldReceive('bind_param')->once()->withArgs(function ($types, $bindings) {
            return $types === 'sdi' && $bindings === ['stripe', 10.00, 1];
        })->andReturn(true);
        
        $statement->shouldReceive('execute')->once()->andReturnTrue();
        $statement->shouldReceive('get_result')->once()->andReturn(
            $result = m::mock(mysqli_result::class)
        );
        
        $result->shouldReceive('fetch_object')->once()->andReturn([]);
        
        $mysqli_driver->doSelect(
            'select * from `payments` where `type` = ? and `amount` = ? and `id` = ?',
            ['stripe', 10.00, 1]
        );
        
        $this->assertTrue(true);
        
        m::close();
    }
    
}
