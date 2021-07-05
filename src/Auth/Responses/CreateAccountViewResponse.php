<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Responses;

    use WPMvc\Auth\Traits\UsesCurrentRequest;
    use WPMvc\Contracts\ResponsableInterface;

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