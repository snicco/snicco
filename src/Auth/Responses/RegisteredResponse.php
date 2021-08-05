<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Responses;

    use Snicco\Auth\Traits\UsesCurrentRequest;
    use Snicco\Contracts\ResponsableInterface;

    abstract class RegisteredResponse implements ResponsableInterface
    {

        use UsesCurrentRequest;

    }