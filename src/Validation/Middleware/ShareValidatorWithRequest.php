<?php


    declare(strict_types = 1);


    namespace BetterWP\Validation\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Validation\Validator;

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