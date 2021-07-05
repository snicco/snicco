<?php


    declare(strict_types = 1);


    namespace WPMvc\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use WPMvc\Contracts\Middleware;
    use WPMvc\ExceptionHandling\Exceptions\AuthorizationException;
    use WPMvc\Support\WP;
    use WPMvc\Http\Psr7\Request;

    class Authorize extends Middleware
    {

        /**
         * @var string
         */
        private $capability;


        /**
         * @var null
         */
        private $object_id;

        /**
         * @var null
         */
        private $key;

        public function __construct(  string $capability = 'manage_options', string $object_id = null, string $key = null)
        {

            $this->capability = $capability;
            $this->object_id = (int) $object_id;
            $this->key = $key;

        }

        public function handle(Request $request, $next):ResponseInterface
        {

            $args = [];
            if ($this->object_id) {
                $args[] = intval($this->object_id);
            }
            if ($this->key) {
                $args[] = $this->key;
            }

            if (WP::currentUserCan($this->capability, ...$args)) {

                return $next($request);

            }

            throw new AuthorizationException('You do not have permission to perform this action');

        }

    }
