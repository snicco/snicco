<?php

declare(strict_types=1);

namespace Snicco\Component\Session\ValueObject;

use function htmlentities;

/**
 * @api
 */
final class CsrfToken
{

    const INPUT_KEY = '_token';
    private string $token_value;

    public function __construct(string $url_safe_token_value)
    {
        $this->token_value = $url_safe_token_value;
    }

    public function asQueryParameter(): string
    {
        return self::INPUT_KEY . '=' . $this->getToken();
    }

    private function getToken(): string
    {
        return $this->token_value;
    }

    public function asMetaProperty(): string
    {
        return '<meta name="'
            . self::INPUT_KEY
            . '" content="'
            . $this->noHtml($this->getToken())
            . '">';
    }

    private function noHtml(string $untrusted): string
    {
        return htmlentities($untrusted, ENT_QUOTES, 'UTF-8');
    }

    public function asInputField(): string
    {
        return '<input type="hidden" name="'
            . self::INPUT_KEY
            . '" value="'
            . $this->noHtml($this->getToken())
            . '">';
    }

    public function asString(): string
    {
        return $this->getToken();
    }

}

