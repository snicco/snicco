<?php

declare(strict_types=1);

namespace Snicco\Bundle\Blade\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use InvalidArgumentException;
use PHPUnit\Framework\Assert as PHPUnit;
use RuntimeException;
use Snicco\Bridge\Blade\BladeViewFactory;
use Snicco\Bundle\Templating\Option\TemplatingOption;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Templating\TemplateEngine;

use WP_UnitTest_Factory;
use WP_User;

use function array_merge;
use function class_exists;
use function dirname;
use function preg_replace;
use function trim;
use function wp_logout;
use function wp_set_current_user;

/**
 * @internal
 */
final class BladeDirectivesTest extends WPTestCase
{
    use BundleTestHelpers;

    private Kernel $kernel;

    /**
     * @psalm-suppress NullArgument
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(Facade::class)) {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);
        }

        if (class_exists(Container::class)) {
            Container::setInstance();
        }

        $this->bundle_test = new BundleTest($this->fixturesDir());

        $this->kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->bundle_test->setUpDirectories()
        );
        $this->kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('templating', [
                TemplatingOption::DIRECTORIES => [$this->fixturesDir() . '/templates'],
                TemplatingOption::VIEW_FACTORIES => [BladeViewFactory::class],
            ]);
        });

        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->bundle_test->tearDownDirectories();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function custom_auth_user_directive_works(): void
    {
        /** @var TemplateEngine $template_engine */
        $template_engine = $this->kernel->container()
            ->get(TemplateEngine::class);

        wp_set_current_user($this->createUserWithRoles('admin')->ID);

        $content = $template_engine->render('auth');
        $this->assertViewContent('AUTHENTICATED', $content);

        wp_logout();

        $content = $template_engine->render('auth');
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function custom_guest_user_directive_works(): void
    {
        /** @var TemplateEngine $template_engine */
        $template_engine = $this->kernel->container()
            ->get(TemplateEngine::class);

        $content = $template_engine->render('guest');
        $this->assertViewContent('YOU ARE A GUEST', $content);

        wp_set_current_user($this->createUserWithRoles('admin')->ID);

        $content = $template_engine->render('guest');
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function custom_wp_role_directives_work(): void
    {
        /** @var TemplateEngine $template_engine */
        $template_engine = $this->kernel->container()
            ->get(TemplateEngine::class);

        $admin = $this->createUserWithRoles('administrator');
        wp_set_current_user($admin->ID);

        $content = $template_engine->render('role');
        $this->assertViewContent('ADMIN', $content);

        $editor = $this->createUserWithRoles('editor');
        wp_set_current_user($editor->ID);

        $content = $template_engine->render('role');
        $this->assertViewContent('EDITOR', $content);

        $author = $this->createUserWithRoles('author');
        wp_set_current_user($author->ID);

        $content = $template_engine->render('role');
        $this->assertViewContent('', $content);
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }

    private function assertViewContent(string $expected, string $actual): void
    {
        $actual = preg_replace('#
|
|	|\\s{2,}#', '', $actual);

        if (null === $actual) {
            throw new RuntimeException('preg_replcae failed in test case.');
        }

        PHPUnit::assertSame($expected, trim($actual), 'View not renderViewed correctly.');
    }

    private function createUserWithRoles(string $role, array $data = []): WP_User
    {
        /** @var WP_UnitTest_Factory $factory */
        $factory = $this->factory();

        $user = $factory->user->create_and_get(array_merge($data, [
            'role' => $role,
        ]));

        if (! $user instanceof WP_User) {
            throw new InvalidArgumentException('Must be WP_USER');
        }

        return $user;
    }
}
