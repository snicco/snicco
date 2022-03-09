<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Snicco\Component\BetterWPDB\MysqliFactory;

final class MysqliFactoryTest extends WPTestCase
{
    /**
     * @test
     */
    public function test_get_mysqli_from_global_wordpress_mysqli(): void
    {
        $mysqli = MysqliFactory::fromWpdbConnection();
        $this->assertTrue($mysqli->ping());
    }
}
