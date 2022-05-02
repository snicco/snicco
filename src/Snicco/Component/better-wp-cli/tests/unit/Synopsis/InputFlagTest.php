<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\unit\Synopsis;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\BetterWPCLI\Synopsis\InputFlag;

/**
 * @internal
 */
final class InputFlagTest extends TestCase
{
    /**
     * @test
     */
    public function basic_configuration(): void
    {
        $flag = new InputFlag('name', 'description');

        $this->assertSame([
            'type' => 'flag',
            'name' => 'name',
            'description' => 'description',
            'optional' => true,
        ], $flag->toArray());
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function non_empty_name_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name can not be empty');
        new InputFlag('', 'description');
    }
}
