<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Tests\wordpress\Functional\Concerns;

use PHPUnit\Framework\AssertionFailedError;
use RuntimeException;
use Snicco\Bundle\Testing\Functional\WebTestCase;

use function dirname;
use function get_current_user_id;

/**
 * @internal
 */
final class AuthenticateWithWordPressTest extends WebTestCase
{
    /**
     * @test
     */
    public function test_login(): void
    {
        $this->assertSame(0, get_current_user_id());

        $admin = $this->createAdmin();

        $this->loginAs($admin);

        $this->assertSame($admin->ID, get_current_user_id());
    }

    /**
     * @test
     */
    public function test_assert_guest(): void
    {
        $this->assertIsGuest();
        $admin = $this->createAdmin();
        $this->loginAs($admin);

        try {
            $this->assertIsGuest();

            throw new RuntimeException('Assertion did not fail.');
        } catch (AssertionFailedError $e) {
            $this->assertStringStartsWith(
                sprintf('The current user [%s] is not a guest.', $admin->ID),
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function test_assert_authenticated(): void
    {
        $admin = $this->createAdmin();
        $editor = $this->createEditor();

        try {
            $this->assertIsAuthenticated($admin);

            throw new RuntimeException('Assertion did not fail.');
        } catch (AssertionFailedError $e) {
            $this->assertStringStartsWith('The current user is a guest.', $e->getMessage());
        }

        $this->loginAs($admin);
        $this->assertIsAuthenticated($admin);
        $this->assertIsAuthenticated($admin->ID);

        $this->loginAs($editor);
        $this->assertIsAuthenticated($editor);

        try {
            $this->assertIsAuthenticated($admin);

            throw new RuntimeException('Assertion did not fail.');
        } catch (AssertionFailedError $e) {
            $this->assertStringStartsWith(
                sprintf('The current user [%s] is not the expected one [%s].', $editor->ID, $admin->ID),
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function test_logout(): void
    {
        $admin = $this->createAdmin();
        $this->loginAs($admin);

        $this->assertIsAuthenticated($admin);

        $this->logout();

        $this->assertIsGuest();
    }

    protected function createKernel(): callable
    {
        return require dirname(__DIR__, 2) . '/fixtures/test-kernel.php';
    }

    protected function extensions(): array
    {
        return [];
    }
}
