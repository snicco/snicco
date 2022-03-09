<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\Identifier;

use Exception;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;

use function sleep;
use function spl_object_hash;

/**
 * @internal
 */
final class SplHashIdentifierTest extends TestCase
{
    /**
     * @test
     */
    public function test_identify_is_spl_hash(): void
    {
        $exception = new Exception('foobar');
        $this->assertSame(
            spl_object_hash($exception),
            (new SplHashIdentifier())->identify($exception)
        );
    }

    /**
     * @test
     */
    public function test_identify_is_pure(): void
    {
        $identifier = new SplHashIdentifier();

        $e = new Exception('foobar');

        $id = $identifier->identify($e);

        $this->assertSame(spl_object_hash($e), $id);

        $this->assertSame($id, $identifier->identify($e));
        $this->assertNotSame(
            $id,
            $new_id = $identifier->identify($new_e = new Exception('foobar'))
        );

        sleep(1);

        $this->assertSame($new_id, $identifier->identify($new_e));
        $this->assertSame($id, $identifier->identify($e));
    }
}
