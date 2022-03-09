<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Assert as PHPUnit;
use RuntimeException;
use Snicco\Bridge\Blade\BladeStandalone;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Snicco\Component\Templating\ViewEngine;
use Symfony\Component\Finder\Finder;

use function class_exists;
use function dirname;
use function preg_replace;
use function trim;
use function unlink;
use function wp_logout;
use function wp_set_current_user;

class CustomDirectivesTest extends WPTestCase
{
    protected string $blade_cache;

    protected string $blade_views;

    protected ViewEngine $view_engine;

    protected ViewComposerCollection $composers;

    protected GlobalViewContext $global_view_context;

    protected BladeStandalone $blade;

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

        $this->composers = new ViewComposerCollection(
            null,
            $global_view_context = new GlobalViewContext()
        );
        $blade = new BladeStandalone($this->blade_cache, [$this->blade_views], $this->composers);
        $blade->boostrap();
        $this->blade = $blade;
        $this->view_engine = new ViewEngine($blade->getBladeViewFactory());
        $this->global_view_context = $global_view_context;

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

        $user = $this->factory()->user->create_and_get();
        wp_set_current_user($user->ID);

        $view = $this->view('auth');
        $content = $view->render();
        $this->assertViewContent('AUTHENTICATED', $content);

        wp_logout();

        $view = $this->view('auth');
        $content = $view->render();
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function custom_guest_user_directive_works(): void
    {
        $this->blade->bindWordPressDirectives(new BetterWPAPI());

        $view = $this->view('guest');
        $content = $view->render();
        $this->assertViewContent('YOU ARE A GUEST', $content);

        wp_set_current_user($this->factory()->user->create_and_get()->ID);

        $view = $this->view('guest');
        $content = $view->render();
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function custom_wp_role_directives_work(): void
    {
        $this->blade->bindWordPressDirectives(new BetterWPAPI());

        $admin = $this->factory()->user->create_and_get([
            'role' => 'administrator',
        ]);
        wp_set_current_user($admin->ID);

        $view = $this->view('role');
        $content = $view->render();
        $this->assertViewContent('ADMIN', $content);

        $editor = $this->factory()->user->create_and_get([
            'role' => 'editor',
        ]);
        wp_set_current_user($editor->ID);

        $view = $this->view('role');
        $content = $view->render();
        $this->assertViewContent('EDITOR', $content);

        $author = $this->factory()->user->create_and_get([
            'role' => 'author',
        ]);
        wp_set_current_user($author->ID);

        $view = $this->view('role');
        $content = $view->render();
        $this->assertViewContent('', $content);
    }

    protected function assertViewContent(string $expected, string $actual): void
    {
        $actual = preg_replace("/\r|\n|\t|\s{2,}/", '', $actual);

        if (null === $actual) {
            throw new RuntimeException('preg_replcae failed in test case.');
        }

        PHPUnit::assertSame($expected, trim($actual), 'View not rendered correctly.');
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
}
