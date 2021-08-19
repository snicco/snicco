<?php

declare(strict_types=1);

namespace Snicco\Http\Responses;

use Snicco\Http\Psr7\Response;

/**
 * Returning this response class will result in the framework not doing anything at all.
 * No headers and no content will be sent.
 * If you want to delegate only the content rendering to WordPress but send headers generated
 * by middleware etc. you should use the @see DelegatedResponse class.
 */
class NullResponse extends Response
{

}