<?php

declare(strict_types=1);

namespace Tests\integration\Mail\ValueObjects;

use InvalidArgumentException;
use Codeception\TestCase\WPTestCase;
use Snicco\Mail\ValueObjects\Attachment;

final class AttachmentTest extends WPTestCase
{
    
    /** @test */
    public function testNewAttachmentWithInvalidFile()
    {
        try {
            new Attachment('foobar', 'foo name');
            $this->fail('attachment created.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringStartsWith(
                "Invalid file path [foobar] provided for attachment.",
                $exception->getMessage()
            );
        }
    }
    
    /** @test */
    public function testAttachmentWithValidFile()
    {
        $attachment = new Attachment(__FILE__, 'my-attachment');
        
        $this->assertSame(__FILE__, $attachment->getPath());
        $this->assertSame('my-attachment', $attachment->getName());
    }
    
}