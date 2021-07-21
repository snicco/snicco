<?php


    declare(strict_types = 1);


    namespace Snicco\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\StreamInterface;
    use RuntimeException;
    use Snicco\Contracts\Middleware;
    use Snicco\ExceptionHandling\Exceptions\HttpException;
    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;
    use Snicco\Support\Str;

    abstract class Payload extends Middleware
    {

        /**
         * @var array
         */
        protected $content_types;

        protected $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        /**
         * @param  StreamInterface  $stream
         *
         * @return array
         * @throws RuntimeException
         */
        abstract protected function parse(StreamInterface $stream) : array;

        public function contentType(array $contentType) : self
        {

            $this->content_types = $contentType;

            return $this;
        }

        public function methods(array $methods) : self
        {

            $this->methods = $methods;

            return $this;
        }

        public function handle(Request $request, Delegate $next):ResponseInterface
        {

            if ( ! $this->shouldParseRequest($request) ) {

                return $next($request);

            }

            try {

                $request = $request->withParsedBody($this->parse($request->getBody()));

                return $next($request);

            }
            catch (RuntimeException $exception) {

                throw new HttpException(400, $exception->getMessage());

            }


        }

        private function shouldParseRequest(Request $request) : bool
        {

            if ( ! in_array($request->getMethod(), $this->methods, true)) {

                return false;

            }

            return Str::contains($request->getHeaderLine('Content-Type'), $this->content_types);

        }


    }