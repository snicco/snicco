<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail;

use Snicco\Component\BetterWPAPI\BetterWPAPI;

use function get_bloginfo;
use function network_home_url;
use function wp_mail;

/**
 * @psalm-internal Snicco\Component\BetterWPMail
 *
 * @interal
 */
final class WPMailAPI extends BetterWPAPI
{

    /**
     * @param string|string[] $to
     * @param string|string[] $headers
     * @param string|string[] $attachments
     */
    public function mail($to, string $subject, string $message, $headers = '', $attachments = []): bool
    {
        return wp_mail($to, $subject, $message, $headers, $attachments);
    }

    /**
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    public function adminEmail(): string
    {
        return $this->applyFilters('wp_mail_from', get_bloginfo('admin_email'));
    }

    /**
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    public function siteName(): string
    {
        return $this->applyFilters('wp_mail_from_name', get_bloginfo('name'));
    }

    public function siteUrl(): string
    {
        return network_home_url();
    }

}