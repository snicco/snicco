<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

/**
 * @internal
 */
trait HashesSessionIds
{
    
    protected function hashedSessionId()
    {
        return $this->hash($this->getSessionId());
    }
    
    protected function hash($id)
    {
        return hash('sha256', $id);
    }
    
    protected function getSessionId() :string
    {
        return str_repeat('a', 64);
    }
    
    protected function anotherSessionId() :string
    {
        return str_repeat('b', 64);
    }
    
}