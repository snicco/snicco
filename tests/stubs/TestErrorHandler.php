<?php


    declare(strict_types = 1);


    namespace Tests\stubs;

    use Tests\helpers\CreatePsr17Factories;
    use Throwable;
    use Snicco\Contracts\ErrorHandlerInterface;
    use Snicco\ExceptionHandling\Exceptions\HttpException;
    use Snicco\Http\Psr7\Response;

