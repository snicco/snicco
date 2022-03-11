<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

use Webmozart\Assert\Assert;

use function array_unique;
use function class_exists;
use function count;

final class Psr17FactoryDiscovery
{
    /**
     * @var array<string,object>
     */
    private array $factories = [];

    /**
     * @var array< string|class-string,
     *              array{
     *                      server_request: class-string|string,
     *                      uri: class-string|string,
     *                      uploaded_file: class-string|string,
     *                      stream: class-string|string,
     *                      response: class-string|string
     * }> $check_for_classes
     */
    private array $check_for_classes;

    /**
     * @param array<class-string,
     *              array{
     *                      server_request: class-string,
     *                      uri: class-string,
     *                      uploaded_file: class-string,
     *                      stream: class-string,
     *                      response: class-string
     * }> $check_for_classes
     */
    public function __construct(?array $check_for_classes = null)
    {
        $this->check_for_classes = $check_for_classes ?: [
            // nyholm-psr7
            \Nyholm\Psr7\Factory\Psr17Factory::class => [
                'server_request' => \Nyholm\Psr7\Factory\Psr17Factory::class,
                'uri' => \Nyholm\Psr7\Factory\Psr17Factory::class,
                'uploaded_file' => \Nyholm\Psr7\Factory\Psr17Factory::class,
                'stream' => \Nyholm\Psr7\Factory\Psr17Factory::class,
                'response' => \Nyholm\Psr7\Factory\Psr17Factory::class,
            ],
            // guzzle
            \GuzzleHttp\Psr7\HttpFactory::class => [
                'server_request' => \GuzzleHttp\Psr7\HttpFactory::class,
                'uri' => \GuzzleHttp\Psr7\HttpFactory::class,
                'uploaded_file' => \GuzzleHttp\Psr7\HttpFactory::class,
                'stream' => \GuzzleHttp\Psr7\HttpFactory::class,
                'response' => \GuzzleHttp\Psr7\HttpFactory::class,
            ],
        ];
    }

    public function createServerRequestFactory(): ServerRequestFactoryInterface
    {
        return $this->getFactory(ServerRequestFactoryInterface::class);
    }

    public function createUriFactory(): UriFactoryInterface
    {
        return $this->getFactory(UriFactoryInterface::class);
    }

    public function createUploadedFileFactory(): UploadedFileFactoryInterface
    {
        return $this->getFactory(UploadedFileFactoryInterface::class);
    }

    public function createStreamFactory(): StreamFactoryInterface
    {
        return $this->getFactory(StreamFactoryInterface::class);
    }

    public function createResponseFactory(): ResponseFactoryInterface
    {
        return $this->getFactory(ResponseFactoryInterface::class);
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @throws ReflectionException
     *
     * @return T
     */
    private function getFactory(string $class): object
    {
        if (isset($this->factories[$class])) {
            $f = $this->factories[$class];
            Assert::isInstanceOf($f, $class);

            return $f;
        }

        switch ($class) {
            case ServerRequestFactoryInterface::class :
                $index = 'server_request';

                break;
            case UploadedFileFactoryInterface::class:
                $index = 'uploaded_file';

                break;
            case StreamFactoryInterface::class:
                $index = 'stream';

                break;
            case UriFactoryInterface::class:
                $index = 'uri';

                break;
            default:
                $index = 'response';
        }

        foreach ($this->check_for_classes as $marker_class => $factory_classes) {
            if (! class_exists($marker_class)) {
                continue;
            }

            /** @var class-string $instance_class */
            $instance_class = $factory_classes[$index];

            $instance = (new ReflectionClass($instance_class))->newInstance();

            if (1 === count(array_unique($factory_classes))) {
                $this->factories[UriFactoryInterface::class] = $instance;
                $this->factories[ServerRequestFactoryInterface::class] = $instance;
                $this->factories[ResponseFactoryInterface::class] = $instance;
                $this->factories[StreamFactoryInterface::class] = $instance;
                $this->factories[UploadedFileFactoryInterface::class] = $instance;
            } else {
                $this->factories[$class] = $instance;
            }
            /**
             * @psalm-var  T $instance
             */
            Assert::isInstanceOf($instance, $class);

            return $instance;
        }

        throw new RuntimeException(sprintf('No PSR-17 factory detected to create a %s', $class));
    }
}
