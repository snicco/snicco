<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Responses;

    use Snicco\Auth\Traits\UsesCurrentRequest;
    use Snicco\Contracts\ResponsableInterface;

    abstract class CreateAccountViewResponse implements ResponsableInterface
    {
        use UsesCurrentRequest;

        protected string $post_to;

        public function postTo(string $path) {
            $this->post_to = $path;
            return $this;
        }

    }