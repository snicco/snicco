<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\Information;

use InvalidArgumentException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;
use Snicco\Component\Psr7ErrorHandler\Information\InformationProviderWithTransformation;

/**
 * @psalm-suppress InvalidArgument
 *
 * @internal
 */
final class TransformableInformationProviderTest extends TestCase
{
    /**
     * @test
     */
    public function test_from_default_data(): void
    {
        $provider = InformationProviderWithTransformation::fromDefaultData(new SplHashIdentifier());

        $e = new HttpException(404, 'secret stuff');

        $information = $provider->createFor($e, new ServerRequest('GET', '/'));

        $this->assertSame(404, $information->statusCode());
        $this->assertSame('Not Found', $information->safeTitle());
        $this->assertSame(
            'The requested resource could not be found but may be available again in the future.',
            $information->safeDetails()
        );
    }

    /**
     * @test
     */
    public function test_exception_if_provided_status_code_smaller_than_400(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$status_code must be greater >= 400.');

        new InformationProviderWithTransformation([
            303 => [
                'title' => 'foo',
                'message' => 'bar',
            ],
        ], new SplHashIdentifier());
    }

    /**
     * @test
     */
    public function test_exception_if_no_title(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$title must be string for status code [403].');

        new InformationProviderWithTransformation([
            403 => [
                'message' => 'bar',
            ],
        ], new SplHashIdentifier());
    }

    /**
     * @test
     */
    public function test_exception_if_no_message(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$message must be string for status code [403].');

        new InformationProviderWithTransformation([
            403 => [
                'title' => 'bar',
            ],
        ], new SplHashIdentifier());
    }
}
