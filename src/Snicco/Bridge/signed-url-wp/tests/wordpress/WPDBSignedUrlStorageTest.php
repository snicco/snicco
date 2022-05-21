<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlWP\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Snicco\Bridge\SignedUrlWP\WPDBSignedUrlStorage;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\SignedUrl\Testing\SignedUrlStorageTests;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\SystemClock;

/**
 * @internal
 */
final class WPDBSignedUrlStorageTest extends WPTestCase
{
    use SignedUrlStorageTests;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createStorage(SystemClock::fromUTC())->createTable();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        BetterWPDB::fromWpdb()->unprepared('DROP TABLE IF EXISTS snicco_signed_url');
    }

    protected function createStorage(Clock $clock): WPDBSignedUrlStorage
    {
        return new WPDBSignedUrlStorage(BetterWPDB::fromWpdb(), 'snicco_signed_url', $clock);
    }
}
