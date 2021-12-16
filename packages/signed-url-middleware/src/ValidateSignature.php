<?php

declare(strict_types=1);

namespace Snicco\SignedUrlMiddleware;

use Closure;
use RuntimeException;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\SignedUrl\SignedUrlValidator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\SignedUrl\Exceptions\SignedUrlException;

final class ValidateSignature implements MiddlewareInterface
{
    
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var SignedUrlValidator
     */
    private $validator;
    
    /**
     * @var callable
     */
    private $renderer;
    
    /**
     * @var array
     */
    private $log_levels;
    
    /**
     * @var Closure|null
     */
    private $request_context;
    
    public function __construct(
        SignedUrlValidator $validator,
        ResponseFactoryInterface $response_factory,
        LoggerInterface $logger,
        $template_renderer = null,
        $log_levels = [],
        ?Closure $request_context = null,
        int $garbage_collection_percentage = 2
    ) {
        $this->validator = $validator;
        $this->response_factory = $response_factory;
        $this->logger = $logger;
        $this->renderer = $template_renderer ? : $this->defaultTemplate();
        $this->log_levels = $log_levels;
        $this->request_context = $request_context;
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        try {
            $context = is_callable($this->request_context)
                ? call_user_func($this->request_context, $request)
                : '';
            
            $this->validator->validate($request->getRequestTarget(), $context);
            
            return $handler->handle($request);
        } catch (SignedUrlException $e) {
            $this->report($e, $request);
        }
        
        $response = $this->response_factory->createResponse(403);
        $template = call_user_func($this->renderer, $request);
        
        $response->getBody()->write((string) $template);
        
        return $response;
    }
    
    private function defaultTemplate() :Closure
    {
        return function () {
            return <<<'EOT'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>403 | Link expired </title>
    <style>html{font-family: sans-serif;}</style>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Your link is expired or invalid. Please request a new one.</h1>
</body>
</html>
EOT;
        };
    }
    
    private function getLogLevel(RuntimeException $e) :string
    {
        $level = $this->log_levels[get_class($e)] ?? LogLevel::WARNING;
        
        if ( ! in_array($level, [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ], true)) {
            throw new InvalidArgumentException("Log level [$level] is not a valid psr/log level.");
        }
        
        return $level;
    }
    
    private function report(SignedUrlException $e, RequestInterface $request)
    {
        $log_level = $this->getLogLevel($e);
        $this->logger->log(
            $log_level,
            $e->getMessage(),
            ['exception' => $e, 'path' => $request->getUri()->getPath()]
        );
    }
    
}