<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail;

use function get_bloginfo;
use function network_home_url;
use function remove_filter;
use function wp_mail;

/**
 * @interal
 */
final class ScopableWP extends \Snicco\Component\ScopableWP\ScopableWP
{

    public function mail($to, $subject, $message, $headers = '', $attachments = []): bool
    {
        return wp_mail($to, $subject, $message, $headers, $attachments);
    }

    public function removeFilter(string $hook_name, $callback, $priority = 10): bool
    {
        return remove_filter($hook_name, $callback, $priority);
    }

    public function adminEmail(): string
    {
        return $this->applyFilters('wp_mail_from', get_bloginfo('admin_email'));
    }

    public function siteName(): string
    {
        return $this->applyFilters('wp_mail_from_name', get_bloginfo('name'));
    }

    public function siteUrl(): string
    {
        return network_home_url();
    }

}