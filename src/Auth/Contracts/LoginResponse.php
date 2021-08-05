<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Contracts;

    use Snicco\Auth\Traits\UsesCurrentRequest;
    use Snicco\Contracts\ResponsableInterface;

    abstract class LoginResponse implements ResponsableInterface
    {
        use UsesCurrentRequest;

    }