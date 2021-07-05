<?php


    declare(strict_types = 1);


    namespace BetterWP\Session;

    use BetterWP\Contracts\EncryptorInterface;
    use BetterWP\ExceptionHandling\Exceptions\DecryptException;
    use BetterWP\ExceptionHandling\Exceptions\EncryptException;
    use BetterWP\Session\Contracts\SessionDriver;

    class EncryptedSession extends Session
    {
        /**
         * @var EncryptorInterface
         */
        protected $encryptor;


        public function __construct( SessionDriver $handler, EncryptorInterface $encryptor, int $strength = 32)
        {
            $this->encryptor = $encryptor;

            parent::__construct( $handler, $strength);
        }

        /**
         * Prepare the raw string data from the session for unserialization.
         *
         * @param  string  $data
         *
         * @return string
         */
        protected function prepareForUnserialize( string $data ) : string
        {
            try {
                return $this->encryptor->decrypt($data);
            } catch (DecryptException $e) {
                return serialize([]);
            }
        }

        /**
         * Prepare the serialized session data for storage.
         *
         * @param  string  $data
         *
         * @return string
         * @throws EncryptException
         */
        protected function prepareForStorage(string $data) : string
        {
            return $this->encryptor->encrypt($data);
        }


    }