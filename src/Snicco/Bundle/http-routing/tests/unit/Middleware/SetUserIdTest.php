<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit\Middleware;

use Snicco\Bundle\HttpRouting\Middleware\SetUserId;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;

final class SetUserIdTest extends MiddlewareTestCase
{

    /**
     * @test
     */
    public function the_user_id_is_added_to_request(): void
    {
        $middleware = new SetUserId(new SetUserIDTestWPAPI(12));
        $response = $this->runMiddleware($middleware, $this->frontendRequest());
        $response->assertNextMiddlewareCalled();

        $this->assertSame(12, $this->receivedRequest()->userId());
    }

}

class SetUserIDTestWPAPI extends BetterWPAPI
{

    private int $user_id;

    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
    }

    public function currentUserId(): int
    {
        return $this->user_id;
    }

}