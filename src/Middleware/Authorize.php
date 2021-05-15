<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use Closure;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Contracts\RequestInterface;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Exceptions\AuthorizationException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;

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

        public function __construct(  string $capability = 'manage_options', $object_id = null, $key = null)
        {

            $this->capability = $capability;
            $this->object_id = $object_id;
            $this->key = $key;

        }

        public function handle(Request $request, $next)
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
