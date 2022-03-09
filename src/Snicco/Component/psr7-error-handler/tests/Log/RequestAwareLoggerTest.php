<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Tests\Log;

use GuzzleHttp\Psr7\ServerRequest;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;
use RuntimeException;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Snicco\Component\Psr7ErrorHandler\Log\RequestAwareLogger;
use Snicco\Component\Psr7ErrorHandler\Log\RequestLogContext;

final class RequestAwareLoggerTest extends TestCase
{
    private ServerRequestInterface $request;
    private TestLogger $test_logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ServerRequest('GET', '/foo');
        $this->test_logger = new TestLogger();
    }

    /**
     * @test
     */
    public function exception_information_is_logged(): void
    {
        $logger = new RequestAwareLogger($test_logger = new TestLogger());

        $info = new ExceptionInformation(
            403,
            'foo_id',
            'title',
            'safe_details',
            $e = new RuntimeException('secret stuff here'),
            HttpException::fromPrevious(403, $e),
            $this->request
        );

        $logger->log($info);

        $this->assertTrue($test_logger->hasErrorRecords());
    }

    /**
     * @test
     */
    public function the_exception_and_identifier_is_included_in_the_log_context(): void
    {
        $logger = new RequestAwareLogger($test_logger = new TestLogger());

        $info = new ExceptionInformation(
            403,
            'foo_id',
            'title',
            'safe_details',
            $e = new RuntimeException('secret stuff here'),
            HttpException::fromPrevious(403, $e),
            $this->request,
        );

        $logger->log($info);

        $this->assertTrue(
            $test_logger->hasError([
                'message' => 'secret stuff here',
                'context' => [
                    'exception' => $e,
                    'identifier' => 'foo_id',
                ],
            ])
        );
    }

    /**
     * @test
     */
    public function exception_information_with_a_500_status_code_or_higher_are_logged_as_critical(): void
    {
        $logger = new RequestAwareLogger($test_logger = new TestLogger());

        $info = new ExceptionInformation(
            500,
            'foo_id',
            'title',
            'safe_details',
            $e = new RuntimeException('secret stuff here'),
            HttpException::fromPrevious(403, $e),
            $this->request,
        );

        $logger->log($info);

        $this->assertTrue(
            $test_logger->hasCritical([
                'message' => 'secret stuff here',
                'context' => [
                    'exception' => $e,
                    'identifier' => 'foo_id',
                ],
            ])
        );
    }

    /**
     * @test
     */
    public function custom_log_levels_can_be_provided(): void
    {
        $logger = new RequestAwareLogger(
            $this->test_logger,
            [
                RuntimeException::class => LogLevel::WARNING,
            ]
        );

        $info = new ExceptionInformation(
            403,
            'foo_id',
            'title',
            'safe_details',
            $e = new RuntimeException('secret stuff here'),
            HttpException::fromPrevious(403, $e),
            $this->request,
        );

        $logger->log($info);

        $this->assertFalse($this->test_logger->hasErrorRecords());
        $this->assertTrue($this->test_logger->hasWarningRecords());

        $this->test_logger->reset();

        $info = new ExceptionInformation(
            403,
            'foo_id',
            'title',
            'safe_details',
            $e = new InvalidArgumentException('secret stuff here'),
            HttpException::fromPrevious(403, $e),
            $this->request,
        );

        $logger->log($info);

        $this->assertFalse($this->test_logger->hasWarningRecords());
        $this->assertTrue($this->test_logger->hasErrorRecords());
    }

    /**
     * @test
     */
    public function request_context_can_be_added_to_the_log_entry(): void
    {
        $logger = new RequestAwareLogger(
            $this->test_logger,
            [],
            new PathLogContext(),
            new MethodLogContext(),
        );

        $info = new ExceptionInformation(
            403,
            'foo_id',
            'title',
            'safe_details',
            $e = new RuntimeException('secret stuff here'),
            HttpException::fromPrevious(403, $e),
            $this->request,
        );

        $logger->log($info);

        $this->assertTrue(
            $this->test_logger->hasError([
                'message' => 'secret stuff here',
                'context' => [
                    'exception' => $e,
                    'identifier' => 'foo_id',
                    'path' => $this->request->getUri()->getPath(),
                    'method' => $this->request->getMethod(),
                ],
            ])
        );
    }
}

class PathLogContext implements RequestLogContext
{
    public function add(array $context, ExceptionInformation $information): array
    {
        $context['path'] = $information->serverRequest()->getUri()->getPath();
        return $context;
    }
}

class MethodLogContext implements RequestLogContext
{
    public function add(array $context, ExceptionInformation $information): array
    {
        $context['method'] = $information->serverRequest()->getMethod();
        return $context;
    }
}
