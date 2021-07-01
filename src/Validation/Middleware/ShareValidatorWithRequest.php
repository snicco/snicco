<?php


    declare(strict_types = 1);


    namespace WPEmerge\Validation\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Validation\Validator;

    class ShareValidatorWithRequest extends Middleware
    {

        /**
         * @var Validator
         */
        private $validator;

        public function __construct(Validator $validator)
        {
            $this->validator = $validator;
        }

        public function handle(Request $request, Delegate $next):ResponseInterface
        {
            return $next($request->withValidator($this->validator));
        }

    }