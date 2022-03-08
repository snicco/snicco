<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing\Tests\wordpress;

use Closure;
use PHPUnit\Framework\AssertionFailedError;
use Snicco\Bundle\Testing\WebTestCase;
use WP_User;

final class CreateWordPressUsersTest extends WebTestCase
{

    /**
     * @test
     */
    public function test_createAdmin(): void
    {
        $admin = $this->createAdmin([
            'user_email' => 'c@web.de'
        ]);
        $this->assertInstanceOf(WP_User::class, $admin);
        $this->assertContains('administrator', $admin->roles);
        $this->assertSame('c@web.de', $admin->user_email);
    }

    /**
     * @test
     */
    public function test_createEditor(): void
    {
        $editor = $this->createEditor();
        $this->assertInstanceOf(WP_User::class, $editor);
        $this->assertContains('editor', $editor->roles);
    }

    /**
     * @test
     */
    public function test_createAuthor(): void
    {
        $editor = $this->createAuthor();
        $this->assertInstanceOf(WP_User::class, $editor);
        $this->assertContains('author', $editor->roles);
    }

    /**
     * @test
     */
    public function test_createContributor(): void
    {
        $editor = $this->createContributor();
        $this->assertInstanceOf(WP_User::class, $editor);
        $this->assertContains('contributor', $editor->roles);
    }

    /**
     * @test
     */
    public function test_createSubscriber(): void
    {
        $subscriber = $this->createSubscriber();
        $this->assertInstanceOf(WP_User::class, $subscriber);
        $this->assertContains('subscriber', $subscriber->roles);
    }

    /**
     * @test
     */
    public function test_assertUserExists(): void
    {
        $subscriber = $this->createSubscriber();

        $this->assertUserExists($subscriber);
        $this->assertUserExists($subscriber->ID);

        try {
            $this->assertUserExists($id = ($subscriber->ID + 1));
            $this->fail('Assertion did not fail.');
        } catch (AssertionFailedError $e) {
            $this->assertStringStartsWith("The user with id [$id] does not exist.", $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function test_assertUserDoesntExists(): void
    {
        $subscriber = $this->createSubscriber();

        $this->assertUserDoesntExists(new WP_User(0));
        $this->assertUserDoesntExists(0);

        try {
            $this->assertUserDoesntExists($id = $subscriber->ID);
            $this->fail('Assertion did not fail.');
        } catch (AssertionFailedError $e) {
            $this->assertStringStartsWith("The user with id [$id] does exist.", $e->getMessage());
        }
    }


    protected function createKernel(): Closure
    {
        return require dirname(__DIR__) . '/fixtures/test-kernel.php';
    }

}