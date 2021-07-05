<?php


    declare(strict_types = 1);


    namespace WPMvc\Validation\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use WPMvc\Contracts\Middleware;
    use WPMvc\Http\Delegate;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Validation\Validator;

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