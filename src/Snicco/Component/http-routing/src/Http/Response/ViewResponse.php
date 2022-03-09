<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\Response;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

use function array_replace;

final class ViewResponse extends Response
{
    private string $view;

    /**
     * @var array<string,mixed>
     */
    private array $view_data = [];

    public function __construct(string $view, ResponseInterface $response)
    {
        $this->view = $view;
        parent::__construct($response->withHeader('content-type', 'text/html; charset=UTF-8'));
    }

    public function view(): string
    {
        return $this->view;
    }

    public function withView(string $view): ViewResponse
    {
        $new = clone $this;
        $new->view = $view;

        return $new;
    }

    /**
     * @return array<string,mixed>
     */
    public function viewData(): array
    {
        return $this->view_data;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function withViewData(array $data): ViewResponse
    {
        $new = clone $this;
        $new->view_data = array_replace($this->view_data, $data);

        return $new;
    }
}
