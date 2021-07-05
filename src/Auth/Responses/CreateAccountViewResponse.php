<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Responses;

    use BetterWP\Auth\Traits\UsesCurrentRequest;
    use BetterWP\Contracts\ResponsableInterface;

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