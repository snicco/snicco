<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Responses;

    use WPEmerge\Auth\Traits\UsesCurrentRequest;
    use WPEmerge\Contracts\ResponsableInterface;

    abstract class CreateAccountViewResponse implements ResponsableInterface
    {
        use UsesCurrentRequest;

        /**
         * @var string
         */
        protected $post_to;

        public function postTo(string $path) {
            $this->post_to = $path;
            return $this;
        }

    }