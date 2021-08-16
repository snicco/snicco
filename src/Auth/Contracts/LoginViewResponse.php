<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Contracts;

    use Snicco\Auth\Traits\UsesCurrentRequest;
    use Snicco\Contracts\ResponseableInterface;

    abstract class LoginViewResponse implements ResponseableInterface
    {
    
        use UsesCurrentRequest;
    }