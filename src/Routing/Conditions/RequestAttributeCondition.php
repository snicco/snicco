<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing\Conditions;

    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Http\Psr7\Request;

    use function collect;

    class RequestAttributeCondition implements ConditionInterface
    {

        /**
         * @var array
         */
        protected $request_arguments;

        public function __construct($arguments_to_match_against)
        {

            $this->request_arguments = collect($arguments_to_match_against);

        }


        public function isSatisfied( Request $request) :bool
        {

            $request = $request->getBody();

            foreach ( $this->request_arguments as $key => $value ) {

                if ( ! in_array($key, array_keys($request), true ) ) {

                    return false;

                }

            }

            $failed_value = $this->request_arguments->first(function ($value, $key) use ($request) {

                return $value !== $request[$key];

            });

            return $failed_value === null;


        }

        public function getArguments(Request $request) : array
        {
            return collect($request->getParsedBody())->only($this->request_arguments->keys())->all();
        }

    }