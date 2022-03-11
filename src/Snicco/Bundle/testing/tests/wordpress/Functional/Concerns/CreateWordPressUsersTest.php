<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Tests\wordpress\Functional\Concerns;

use PHPUnit\Framework\AssertionFailedError;
use Snicco\Bundle\Testing\Functional\WebTestCase;
use WP_User;

use function dirname;

/**
 * @internal
 */
final class CreateWordPressUsersTest extends WebTestCase
{
    /**
     * @test
     */
    public function test_create_admin(): void
    {
        $admin = $this->createAdmin([
            'user_email' => 'c@web.de',
        ]);
        $this->assertInstanceOf(WP_User::class, $admin);
        $this->assertContains('administrator', $admin->roles);
        $this->assertSame('c@web.de', $admin->user_email);
    }

    /**
     * @test
     */
    public function test_create_editor(): void
    {
        $editor = $this->createEditor();
        $this->assertInstanceOf(WP_User::class, $editor);
        $this->assertContains('editor', $editor->roles);
    }

    /**
     * @test
     */
    public function test_create_author(): void
    {
        $editor = $this->createAuthor();
        $this->assertInstanceOf(WP_User::class, $editor);
        $this->assertContains('author', $editor->roles);
    }

    /**
     * @test
     */
    public function test_create_contributor(): void
    {
        $editor = $this->createContributor();
        $this->assertInstanceOf(WP_User::class, $editor);
        $this->assertContains('contributor', $editor->roles);
    }

    /**
     * @test
     */
    public function test_create_subscriber(): void
    {
        $subscriber = $this->createSubscriber();
        $this->assertInstanceOf(WP_User::class, $subscriber);
        $this->assertContains('subscriber', $subscriber->roles);
    }

    /**
     * @test
     */
    public function test_assert_user_exists(): void
    {
        $subscriber = $this->createSubscriber();

        $this->assertUserExists($subscriber);
        $this->assertUserExists($subscriber->ID);
        $id = $subscriber->ID + 1;

        try {
            $this->assertUserExists($id);
            $this->fail('Assertion did not fail.');
        } catch (AssertionFailedError $e) {
            $this->assertStringStartsWith(sprintf('The user with id [%s] does not exist.', $id), $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function test_assert_user_doesnt_exists(): void
    {
        $subscriber = $this->createSubscriber();

        $this->assertUserDoesntExists(new WP_User(0));
        $this->assertUserDoesntExists(0);
        $id = $subscriber->ID;

        try {
            $this->assertUserDoesntExists($id);
            $this->fail('Assertion did not fail.');
        } catch (AssertionFailedError $e) {
            $this->assertStringStartsWith(sprintf('The user with id [%s] does exist.', $id), $e->getMessage());
        }
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
