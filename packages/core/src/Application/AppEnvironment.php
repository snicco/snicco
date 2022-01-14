<?php

declare(strict_types=1);

namespace Snicco\Core\Application;

use Webmozart\Assert\Assert;
use InvalidArgumentException;

use const PHP_SAPI;

final class AppEnvironment
{
    
    const TESTING = 'testing';
    const PROD = 'prod';
    const DEV = 'dev';
    const STAGING = 'staging';
    
    private string $environment;
    
    private bool $is_debug;
    
    private function __construct(string $environment, bool $is_debug)
    {
        Assert::stringNotEmpty(
            $environment,
            'App environment can not be constructed with an empty string.'
        );
        
        if ( ! in_array($environment, $this->validEnvironments(), true)) {
            throw new InvalidArgumentException(
                sprintf(
                    "App environment has to be one of [%s]. Got: [%s].",
                    implode(',', $this->validEnvironments()),
                    $environment
                )
            );
        }
        
        $this->environment = $environment;
        
        if ($this->isProduction() && true === $is_debug) {
            throw new InvalidArgumentException(
                "App environment can not be in debug mode while in production."
            );
        }
        
        $this->is_debug = $is_debug;
    }
    
    public static function fromString(string $environment, bool $debug = false) :self
    {
        return new self($environment, $debug);
    }
    
    public static function production() :AppEnvironment
    {
        return new self(self::PROD, false);
    }
    
    public static function testing(bool $debug = false) :AppEnvironment
    {
        return new self(self::TESTING, $debug);
    }
    
    public static function dev(bool $debug = true) :AppEnvironment
    {
        return new self(self::DEV, $debug);
    }
    
    public static function staging(bool $debug = false) :AppEnvironment
    {
        return new self(self::STAGING, $debug);
    }
    
    public function asString() :string
    {
        return $this->environment;
    }
    
    public function isProduction() :bool
    {
        return self::PROD === $this->environment;
    }
    
    public function isDevelop() :bool
    {
        return self::DEV === $this->environment;
    }
    
    public function isTesting() :bool
    {
        return self::TESTING === $this->environment;
    }
    
    public function isStaging() :bool
    {
        return self::STAGING === $this->environment;
    }
    
    public function isDebug() :bool
    {
        return $this->is_debug;
    }
    
    public function isCli() :bool
    {
        return (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
    }
    
    private function validEnvironments() :array
    {
        return [self::TESTING, self::PROD, self::DEV, self::STAGING];
    }
    
}