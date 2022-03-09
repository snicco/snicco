<?php

declare(strict_types=1);


namespace Snicco\Bundle\Templating;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewEngine;

use function array_filter;

final class TemplatingExceptionDisplayer implements ExceptionDisplayer
{
    private ViewEngine $engine;

    /**
     * @var array<string,View>
     */
    private array $views = [];

    public function __construct(ViewEngine $engine)
    {
        $this->engine = $engine;
    }

    public function display(ExceptionInformation $exception_information): string
    {
        $view = $this->getView($exception_information);

        $view = $view->with([
            'safe_title' => $exception_information->safeTitle(),
            'safe_details' => $exception_information->safeDetails(),
            'identifier' => $exception_information->identifier(),
            'status_code' => $exception_information->statusCode(),
        ]);

        return $view->render();
    }

    public function supportedContentType(): string
    {
        return 'text/html';
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function canDisplay(ExceptionInformation $exception_information): bool
    {
        try {
            $this->getView($exception_information);
            return true;
        } catch (ViewNotFound $e) {
            return false;
        }
    }

    /**
     * @throws ViewNotFound
     */
    private function getView(ExceptionInformation $information): View
    {
        $request = Request::fromPsr($information->serverRequest());
        $is_admin = $request->isToAdminArea();

        if (!isset($this->views[$information->identifier()])) {
            $status = (string)$information->statusCode();
            $possible_views = array_filter([
                $is_admin ? "$status-admin" : null,
                $is_admin ? "errors.$status-admin" : null,
                $is_admin ? "exceptions.$status-admin" : null,
                $status,
                "errors.$status",
                "exceptions.$status",
            ]);
            $this->views[$information->identifier()] = $this->engine->make($possible_views);
        }

        return $this->views[$information->identifier()];
    }
}
