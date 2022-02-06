<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Condition;

use BadMethodCallException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;

/**
 * @api
 */
class QueryStringCondition extends AbstractRouteCondition
{

    /**
     * @var array<string,string>
     */
    private array $query_string_arguments;

    private bool $valid = false;

    /**
     * @param array<string,string> $query_string_arguments
     */
    public function __construct(array $query_string_arguments)
    {
        $this->query_string_arguments = $query_string_arguments;
    }

    public function isSatisfied(Request $request): bool
    {
        $query_args = $request->getQueryParams();

        foreach ($this->query_string_arguments as $key => $value) {
            if (!in_array($key, array_keys($query_args), true)) {
                return false;
            }
            if ($value !== $query_args[$key]) {
                return false;
            }
        }

        $this->valid = true;

        return true;
    }

    public function getArguments(Request $request): array
    {
        if (!$this->valid) {
            throw new BadMethodCallException('Condition arguments were retrieved before condition was validated.');
        }
        return $this->query_string_arguments;
    }

}