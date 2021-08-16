<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Responses;

    use Snicco\Auth\Traits\UsesCurrentRequest;
    use Snicco\Contracts\ResponseableInterface;

    abstract class RegisteredResponse implements ResponseableInterface
    {
    
        use UsesCurrentRequest;
    }