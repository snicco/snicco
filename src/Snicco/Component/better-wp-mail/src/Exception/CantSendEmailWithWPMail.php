<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Exception;

use RuntimeException;
use Throwable;
use WP_Error;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final class CantSendEmailWithWPMail extends RuntimeException implements CantSendEmail
{
    private string $debug_data;

    public function __construct(string $message = '', string $debug_data = '', Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->debug_data = $debug_data;
    }

    public static function becauseWPMailRaisedErrors(WP_Error $error): CantSendEmailWithWPMail
    {
        /** @var string[] $errors */
        $errors = $error->get_error_messages('wp_mail_failed');
        $message = implode("\n", $errors);

        $extra = json_encode($error->errors, JSON_THROW_ON_ERROR);

        return new self(
            "wp_mail() failure. Message: [$message].",
            "Errors: [$extra]."
        );
    }

    public function getDebugData(): string
    {
        return $this->debug_data;
    }
}
