<?php


    declare(strict_types = 1);


    namespace WPEmerge\ServiceProviders;

    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Contracts\RequestInterface;

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


        public function isSatisfied(RequestInterface $request) :bool
        {

            $request = $request->request();

            foreach ( $this->request_arguments as $key => $value ) {

                if ( ! in_array($key, array_keys($request) ) ) {

                    return false;

                }

            }

            $failed_value = $this->request_arguments->first(function ($value, $key) use ($request) {

                return $value !== $request[$key];

            });

            return $failed_value === null;


        }

        public function getArguments(RequestInterface $request) : array
        {
            return collect($request->request())->only($this->request_arguments->keys())->all();
        }

    }