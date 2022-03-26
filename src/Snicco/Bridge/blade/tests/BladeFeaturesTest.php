<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Snicco\Component\Templating\ValueObject\View;
use stdClass;

/**
 * @internal
 */
final class BladeFeaturesTest extends BladeTestCase
{
    /**
     * @test
     */
    public function xss_protection_works(): void
    {
        $view = $this->view('xss');

        $this->assertStringStartsWith('&lt;script', $this->view_engine->renderView($view));
    }

    /**
     * @test
     */
    public function xss_encoding_can_be_disabled(): void
    {
        $view = $this->view('xss-disabled');
        $view = $view->with('script', '<script type="text/javascript">alert("Hacked!");</script>');

        $this->assertStringStartsWith('<script', $this->view_engine->renderView($view));
    }

    /**
     * @test
     */
    public function json_works(): void
    {
        $view = $this->view('json');
        $view = $view->with('json', [
            'foo' => 'bar',
        ]);
        $content = $this->view_engine->renderView($view);
        $this->assertSame([
            'foo' => 'bar',
        ], json_decode($content, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @test
     */
    public function if_works(): void
    {
        $view = $this->view('if');
        $view = $view->with('records', ['foo']);

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('I have one record!', $content);

        $view = $this->view('if');
        $view = $view->with('records', ['foo', 'bar']);

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('I have multiple records!', $content);

        $view = $this->view('if');
        $view = $view->with('records', []);

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent("I don't have any records!", $content);
    }

    /**
     * @test
     */
    public function unless_works(): void
    {
        $view = $this->view('unless');
        $view = $view->with('foo', 'foo');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('', $content);

        $view = $this->view('unless');
        $view = $view->with('foo', 'bar');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('UNLESS', $content);
    }

    /**
     * @test
     */
    public function empty_isset_works(): void
    {
        $view = $this->view('isset-empty');
        $view = $view->with('isset', 'foo');
        $view = $view->with('empty', 'blabla');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('ISSET', $content);

        $view = $this->view('isset-empty');
        $view = $view->with('empty', '');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('EMPTY', $content);
    }

    /**
     * @test
     */
    public function including_a_subview_works(): void
    {
        $view = $this->view('parent');
        $view = $view->with('greeting', 'Hello');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('Hello calvin', $content);
    }

    /**
     * @test
     */
    public function include_if_works(): void
    {
        $view = $this->view('include-if');
        $view = $view->with('greeting', 'Hello');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('Hello calvin', $content);

        $view = $this->view('include-if-bogus');
        $view = $view->with('greeting', 'Hello');

        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function include_when_works(): void
    {
        $view = $this->view('include-when');
        $view = $view->with([
            'greeting' => 'Hello',
            'foo' => 'foo',
        ]);
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('Hello calvin', $content);

        $view = $this->view('include-when');
        $view = $view->with([
            'greeting' => 'Hello',
            'foo' => 'bogus',
        ]);
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function include_unless_works(): void
    {
        $view = $this->view('include-unless');
        $view = $view->with([
            'greeting' => 'Hello',
            'foo' => 'foo',
        ]);
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('', $content);

        $view = $this->view('include-unless');
        $view = $view->with([
            'greeting' => 'Hello',
            'foo' => 'bar',
        ]);
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('Hello Calvin', $content);
    }

    /**
     * @test
     */
    public function include_first_works(): void
    {
        $view = $this->view('include-first');
        $view = $view->with([
            'greeting' => 'Hello',
            'foo' => 'foo',
        ]);
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('Hello Calvin', $content);
    }

    /**
     * @test
     */
    public function each_works(): void
    {
        $user1 = new stdClass();
        $user1->first_name = 'Calvin';

        $user2 = new stdClass();
        $user2->first_name = 'John';

        $user3 = new stdClass();
        $user3->first_name = 'Jane';

        $collection = [$user1, $user2, $user3];

        $view = $this->view('each');
        $view = $view->with([
            'users' => $collection,
        ]);
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('Calvin.John.Jane.', $content);

        $view = $this->view('each');
        $view = $view->with([
            'users' => [],
        ]);
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('NO USERS', $content);
    }

    /**
     * @test
     */
    public function raw_php_works(): void
    {
        $view = $this->view('raw-php');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('10', $content);
    }

    /**
     * @test
     */
    public function section_directives_work(): void
    {
        $view = $this->view('section-child');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('FOOBAZ', $content);
    }

    /**
     * @test
     */
    public function php_files_can_be_rendered(): void
    {
        $view = $this->view_engine->make('blade-features.php-file');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('PHPONLYFILE', $content);

        $view = $this->view_engine->make('blade-features.php-file.php');
        $content = $this->view_engine->renderView($view);
        $this->assertViewContent('PHPONLYFILE', $content);
    }

    private function view(string $view): View
    {
        return $this->view_engine->make('blade-features.' . $view);
    }
}
