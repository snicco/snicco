<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\helpers;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\NullLogger;
use Snicco\Component\Psr7ErrorHandler\Displayer\FallbackDisplayer;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\CanDisplay;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\ContentType;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Delegating;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\Verbosity;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;
use Snicco\Component\Psr7ErrorHandler\Identifier\SplHashIdentifier;
use Snicco\Component\Psr7ErrorHandler\Information\InformationProviderWithTransformation;
use Snicco\Component\Psr7ErrorHandler\Log\RequestAwareLogger;

trait CreateHttpErrorHandler
{

    public function createHttpErrorHandler(ResponseFactoryInterface $response_factory): HttpErrorHandlerInterface
    {
        return new HttpErrorHandler(
            $response_factory,
            new RequestAwareLogger(new NullLogger()),
            InformationProviderWithTransformation::fromDefaultData(new SplHashIdentifier()),
            new FallbackDisplayer(),
            new Delegating(new CanDisplay(), new ContentType(), new Verbosity(true)),
        );
    }

}