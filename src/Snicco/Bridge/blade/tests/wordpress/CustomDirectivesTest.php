<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use InvalidArgumentException;
use PHPUnit\Framework\Assert as PHPUnit;
use RuntimeException;
use Snicco\Bridge\Blade\BladeStandalone;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\TemplateEngine;
use Snicco\Component\Templating\ValueObject\View;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Symfony\Component\Finder\Finder;
use WP_UnitTest_Factory;
use WP_User;

use function array_merge;
use function class_exists;
use function dirname;
use function preg_replace;
use function trim;
use function unlink;
use function wp_logout;
use function wp_set_current_user;

/**
 * @internal
 */
final class CustomDirectivesTest extends WPTestCase
{
    private string $blade_cache;

    private string $blade_views;

    private TemplateEngine $view_engine;

    private ViewComposerCollection $composers;

    private BladeStandalone $blade;

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

        $this->blade_cache = dirname(__DIR__) . '/fixtures/cache';
        $this->blade_views = dirname(__DIR__) . '/fixtures/views';

        $this->composers = new ViewComposerCollection(null, $global_view_context = new GlobalViewContext());
        $blade = new BladeStandalone($this->blade_cache, [$this->blade_views], $this->composers);
        $blade->boostrap();
        $this->blade = $blade;

        $this->view_engine = new TemplateEngine($blade->getBladeViewFactory());

        $this->clearCache();
    }

    protected function tearDown(): void
    {
        $this->clearCache();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function custom_auth_user_directive_works(): void
    {
        $this->blade->bindWordPressDirectives(new BetterWPAPI());

        wp_set_current_user($this->createUserWithRoles('admin')->ID);

        $view = $this->view('auth');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('AUTHENTICATED', $content);

        wp_logout();

        $view = $this->view('auth');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function custom_guest_user_directive_works(): void
    {
        $this->blade->bindWordPressDirectives(new BetterWPAPI());

        $view = $this->view('guest');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('YOU ARE A GUEST', $content);

        wp_set_current_user($this->createUserWithRoles('admin')->ID);

        $view = $this->view('guest');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function custom_wp_role_directives_work(): void
    {
        $this->blade->bindWordPressDirectives(new BetterWPAPI());

        $admin = $this->createUserWithRoles('administrator');
        wp_set_current_user($admin->ID);

        $view = $this->view('role');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('ADMIN', $content);

        $editor = $this->createUserWithRoles('editor');
        wp_set_current_user($editor->ID);

        $view = $this->view('role');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('EDITOR', $content);

        $author = $this->createUserWithRoles('author');
        wp_set_current_user($author->ID);

        $view = $this->view('role');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('', $content);
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

    private function view(string $view): View
    {
        return $this->view_engine->make('blade-features.' . $view);
    }

    private function clearCache(): void
    {
        $files = Finder::create()->in([$this->blade_cache])->ignoreDotFiles(true);
        foreach ($files as $file) {
            unlink($file->getRealPath());
        }
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
