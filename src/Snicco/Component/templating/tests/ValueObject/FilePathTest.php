<?php

declare(strict_types=1);


namespace Snicco\Component\Templating\Tests\ValueObject;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Templating\Exception\InvalidFile;
use Snicco\Component\Templating\ValueObject\FilePath;

final class FilePathTest extends TestCase
{

    /**
     * @test
     */
    public function that_a_valid_class_can_be_created(): void
    {
        $path = FilePath::fromString(__FILE__);
        $this->assertSame(__FILE__, (string)$path);
    }

    /**
     * @test
     */
    public function that_an_invalid_file_throws_an_exception(): void
    {
        $this->expectException(InvalidFile::class);
        FilePath::fromString(__DIR__ . 'bogusbogus.php');
    }

}