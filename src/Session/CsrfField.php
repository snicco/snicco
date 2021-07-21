<?php


    declare(strict_types = 1);


    namespace Snicco\Session;

    use Slim\Csrf\Guard;
    use Snicco\Support\Arr;

    class CsrfField
    {

        /**
         * @var Session
         */
        private $session;

        /**
         * @var Guard
         */
        private $guard;

        public function __construct(Session $session, Guard $guard)
        {

            $this->session = $session;
            $this->guard = $guard;
        }

        public function create() : array
        {

            $name_key = $this->guard->getTokenNameKey();
            $token_key = $this->guard->getTokenValueKey();

            $csrf = $this->session->get('csrf', []);

            if ($csrf !== []) {

                [$name_key_value, $token_value] = [Arr::firstKey($csrf), Arr::firstEl($csrf)];

            }
            else {
                [$name_key_value, $token_value] = $this->persistNewKeyPairInSession();
            }

            return [
                $name_key => $name_key_value,
                $token_key => $token_value,
            ];

        }

        public function asStringToken() : string
        {
            $csrf = $this->create();
            $name = Arr::pullNextPair($csrf);
            $token = Arr::pullNextPair($csrf);

            return Arr::firstKey($name).'='.Arr::firstEl($name).'&'.Arr::firstKey($token).'='.Arr::firstEl($token);
        }

        public function asHtml()
        {

            $field = $this->create();

            $name = Arr::pullNextPair($field);
            $token = Arr::pullNextPair($field);

            ob_start();

            ?><input type="hidden" name="<?= esc_attr(Arr::firstKey($name)); ?>"
                     value="<?= esc_attr(Arr::firstEl($name)); ?>">
            <input type="hidden" name="<?= esc_attr(Arr::firstKey($token)); ?>"
                   value="<?= esc_attr(Arr::firstEl($token)); ?>"><?php

            return ob_get_clean();

        }

        public function refreshToken()
        {

            $this->session->forget('csrf');
            $this->persistNewKeyPairInSession();
        }

        private function persistNewKeyPairInSession() : array
        {

            return array_values($this->guard->generateToken());
        }


    }