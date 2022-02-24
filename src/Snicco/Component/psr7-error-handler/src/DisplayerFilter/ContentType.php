<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler\DisplayerFilter;

use Psr\Http\Message\RequestInterface;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

use function array_filter;
use function strstr;

/**
 * @note This Filter assumes that content negotiation already happened and that the request has the
 *       best negotiated content type filter already set. @see
 *      {https://github.com/middlewares/negotiation/blob/master/src/ContentType.php#L156}
 */
final class ContentType implements DisplayerFilter
{

    public function filter(array $displayers, RequestInterface $request, ExceptionInformation $info): array
    {
        $accept_header = $this->parse($request->getHeaderLine('accept'));

        return array_filter($displayers,
            fn($displayer) => $displayer->supportedContentType() === $accept_header
        );
    }

    private function parse(string $accept): string
    {
        $result = strstr($accept, ',', true);

        $first = $result === false ? $accept : $result;

        $result = strstr($first, ';', true);

        return $result === false ? $first : $result;
    }

}