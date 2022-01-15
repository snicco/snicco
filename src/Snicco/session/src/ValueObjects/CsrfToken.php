<?php

declare(strict_types=1);

namespace Snicco\Session\ValueObjects;

use function esc_attr;

/**
 * @api
 */
final class CsrfToken
{
    
    const INPUT_KEY = '_token';
    
    /**
     * @var string
     */
    private $token_value;
    
    public function __construct(string $url_safe_token_value)
    {
        $this->token_value = $url_safe_token_value;
    }
    
    public function asQueryParameter() :string
    {
        return self::INPUT_KEY.'='.$this->getToken();
    }
    
    public function asMetaProperty() :string
    {
        return '<meta name="'
               .self::INPUT_KEY
               .'" content="'
               .esc_attr($this->getToken())
               .'">';
    }
    
    public function asInputField() :string
    {
        return '<input type="hidden" name="'
               .self::INPUT_KEY
               .'" value="'
               .esc_attr($this->getToken())
               .'">';
    }
    
    public function asString() :string
    {
        return $this->getToken();
    }
    
    private function getToken() :string
    {
        return $this->token_value;
    }
    
}

