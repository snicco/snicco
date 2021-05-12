<?php


    declare(strict_types = 1);


    namespace WPEmerge\ServiceProviders;

    use WPEmerge\Contracts\RequestInterface;

    class AdminAjaxCondition extends RequestAttributeCondition
    {


        public function isSatisfied(RequestInterface $request) :bool
        {

            $expected_action = $this->request_arguments->get('action', '');

            return parent::isSatisfied($request)
                || $request->query('action') === $expected_action;


        }

        public function getArguments(RequestInterface $request) : array
        {

            return array_merge(
                parent::getArguments($request),
                [ $request->query('action', []) ]
            );
        }



    }