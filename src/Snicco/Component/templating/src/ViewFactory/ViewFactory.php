<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\ValueObject\View;

interface ViewFactory
{
    /**
     * This method must treat every "." in the view name as a directory
     * separator, except the dots that belong to the file extension.
     *
     * "user.account.php" => "/user/account.php" "custom.user.account.html.twig"
     * => "/custom/user/account.html.twig"
     *
     * @throws ViewNotFound
     */
    public function make(string $view): View;

    /**
     * Returns the views evaluated content as a string without echoing.
     *
     * @throws ViewCantBeRendered
     */
    public function toString(View $view): string;
}
