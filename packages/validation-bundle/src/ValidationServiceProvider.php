<?php

declare(strict_types=1);

namespace Snicco\Validation;

use Snicco\Http\Psr7\Request;
use Respect\Validation\Factory;
use Snicco\Contracts\ServiceProvider;
use Snicco\ExceptionHandling\ProductionExceptionHandler;
use Snicco\Contracts\ExceptionHandler;
use Snicco\Validation\Exceptions\ValidationException;
use Snicco\Validation\Middleware\ShareValidatorWithRequest;

class ValidationServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        $this->bindValidator();
        $this->addRuleNamespace();
    }
    
    function bootstrap() :void
    {
        $this->renderValidationExceptions();
    }
    
    private function bindConfig()
    {
        $this->config->extend('validation.messages', []);
        $this->config->extend('middleware.groups.global', [ShareValidatorWithRequest::class]);
        $this->config->extend('middleware.unique', [ShareValidatorWithRequest::class]);
    }
    
    private function bindValidator()
    {
        $this->container->singleton(Validator::class, function () {
            $validator = new Validator();
            $validator->globalMessages($this->config->get('validation.messages'));
            
            return $validator;
        });
    }
    
    private function addRuleNamespace()
    {
        Factory::setDefaultInstance(
            (new Factory())
                ->withRuleNamespace('Snicco\Validation\Rules')
                ->withExceptionNamespace('Snicco\Validation\Exceptions')
        );
    }
    
    private function renderValidationExceptions()
    {
        $error_handler = $this->container->make(ExceptionHandler::class);
        
        if ( ! $error_handler instanceof ProductionExceptionHandler) {
            return;
        }
        
        $callback = function (ValidationException $e, Request $request) {
            return (new ValidationExceptionRenderer(
                
                $this->response_factory,
                $this->dont_flash
            
            ))->render($e, $request);
        };
        
        $error_handler->renderable(
            $callback->bindTo($error_handler, ProductionExceptionHandler::class)
        );
    }
    
}