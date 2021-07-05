<?php


    declare(strict_types = 1);


    namespace Tests\stubs;

    use Tests\helpers\CreatePsr17Factories;
    use Throwable;
    use WPMvc\Contracts\ErrorHandlerInterface;
    use WPMvc\ExceptionHandling\Exceptions\HttpException;
    use WPMvc\Http\Psr7\Response;

