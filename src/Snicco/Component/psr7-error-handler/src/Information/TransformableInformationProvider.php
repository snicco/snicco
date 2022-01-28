<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\Information;

use Throwable;
use InvalidArgumentException;
use Snicco\Component\Psr7ErrorHandler\UserFacing;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\Psr7ErrorHandler\Identifier\ExceptionIdentifier;

/**
 * @api
 */
final class TransformableInformationProvider implements InformationProvider
{
    
    private ExceptionIdentifier $identifier;
    
    /**
     * @var array<int,array<string,string>>
     */
    private array $default_messages;
    
    /**
     * @var ExceptionTransformer[]
     */
    private array $transformer;
    
    public function __construct(array $data, ExceptionIdentifier $identifier, ExceptionTransformer ...$transformer)
    {
        foreach ($data as $status_code => $title_and_details) {
            $this->addMessage($status_code, $title_and_details);
        }
        if ( ! isset($this->default_messages[500])) {
            throw new InvalidArgumentException(
                "Data for the 500 status code must be provided."
            );
        }
        $this->transformer = $transformer;
        $this->identifier = $identifier;
    }
    
    public function createFor(Throwable $e) :ExceptionInformation
    {
        $original = $e;
        $transformed = $this->transform($original);
        
        $status = $transformed instanceof HttpException ? $transformed->statusCode() : 500;
        
        [$title, $details] = $this->getData($status, $transformed, $original);
        
        return new ExceptionInformation(
            $status,
            $this->identifier->identify($original),
            $title,
            $details,
            $original,
            $transformed,
        );
    }
    
    private function addMessage(int $status_code, array $info) :void
    {
        if ( ! isset($info['title'])) {
            throw new InvalidArgumentException("Missing key title for status code [$status_code]");
        }
        if ( ! isset($info['details'])) {
            throw new InvalidArgumentException(
                "Missing key details for status code [$status_code]"
            );
        }
        $this->default_messages[$status_code] = $info;
    }
    
    private function transform(Throwable $throwable) :Throwable
    {
        $transformed = $throwable;
        
        foreach ($this->transformer as $transformer) {
            $transformed = $transformer->transform($transformed);
        }
        
        return $transformed;
    }
    
    private function getData(int $status_code, Throwable $transformed, Throwable $original) :array
    {
        $info = $this->default_messages[$status_code] ?? $this->default_messages[500];
        $title = $info['title'];
        $details = $info['details'];
        
        if ($original instanceof UserFacing) {
            $title = $original->title();
            $details = $original->safeDetails();
        }
        
        if ($transformed instanceof UserFacing) {
            $title = $transformed->title();
            $details = $transformed->safeDetails();
        }
        
        return [$title, $details];
    }
    
}