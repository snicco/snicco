<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPMail\Tests\wordpress\ValueObjects;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use LogicException;
use Snicco\Component\BetterWPMail\ValueObject\Attachment;

final class AttachmentTest extends WPTestCase
{

    /**
     * @test
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_exception_for_non_string_non_resource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("\$data must be string or resource.\nGot [integer].");

        Attachment::fromData(1, 'foo.php');
    }

    /**
     * @test
     */
    public function test_exception_if_calling_cid_for_non_inline_attachment(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Attachment is not embedded and has no cid.');
        $attachment = Attachment::fromData('foo', 'foo.php');

        $attachment->cid();
    }

    /**
     * @test
     */
    public function test_exception_for_unreadable_path(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not readable');

        Attachment::fromPath(__DIR__ . '/bogus.php');
    }

}