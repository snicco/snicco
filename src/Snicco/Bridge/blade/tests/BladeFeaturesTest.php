<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\View\View;
use stdClass;

class BladeFeaturesTest extends BladeTestCase
{

    /**
     * @test
     */
    public function xss_protection_works(): void
    {
        $view = $this->view('xss');

        $this->assertStringStartsWith('&lt;script', $view->render());
    }

    /**
     * @test
     */
    public function xss_encoding_can_be_disabled(): void
    {
        $view = $this->view('xss-disabled');
        $view = $view->with('script', '<script type="text/javascript">alert("Hacked!");</script>');

        $this->assertStringStartsWith('<script', $view->render());
    }

    /**
     * @test
     */
    public function json_works(): void
    {
        $view = $this->view('json');
        $view = $view->with('json', ['foo' => 'bar']);
        $content = $view->render();
        $this->assertSame(['foo' => 'bar'], json_decode($content, true));
    }

    /**
     * @test
     */
    public function if_works(): void
    {
        $view = $this->view('if');
        $view = $view->with('records', ['foo']);
        $content = $view->render();
        $this->assertViewContent('I have one record!', $content);

        $view = $this->view('if');
        $view = $view->with('records', ['foo', 'bar']);
        $content = $view->render();
        $this->assertViewContent('I have multiple records!', $content);

        $view = $this->view('if');
        $view = $view->with('records', []);
        $content = $view->render();
        $this->assertViewContent("I don't have any records!", $content);
    }

    /**
     * @test
     */
    public function unless_works(): void
    {
        $view = $this->view('unless');
        $view = $view->with('foo', 'foo');
        $content = $view->render();
        $this->assertViewContent('', $content);

        $view = $this->view('unless');
        $view = $view->with('foo', 'bar');
        $content = $view->render();
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
        $content = $view->render();
        $this->assertViewContent('ISSET', $content);

        $view = $this->view('isset-empty');
        $view = $view->with('empty', '');
        $content = $view->render();
        $this->assertViewContent('EMPTY', $content);
    }

    /**
     * @test
     */
    public function including_a_subview_works(): void
    {
        $view = $this->view('parent');
        $view = $view->with('greeting', 'Hello');
        $content = $view->render();
        $this->assertViewContent('Hello calvin', $content);
    }

    /**
     * @test
     */
    public function include_if_works(): void
    {
        $view = $this->view('include-if');
        $view = $view->with('greeting', 'Hello');
        $content = $view->render();
        $this->assertViewContent('Hello calvin', $content);

        $view = $this->view('include-if-bogus');
        $view = $view->with('greeting', 'Hello');
        $content = $view->render();
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function include_when_works(): void
    {
        $view = $this->view('include-when');
        $view = $view->with(['greeting' => 'Hello', 'foo' => 'foo']);
        $content = $view->render();
        $this->assertViewContent('Hello calvin', $content);

        $view = $this->view('include-when');
        $view = $view->with(['greeting' => 'Hello', 'foo' => 'bogus']);
        $content = $view->render();
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function include_unless_works(): void
    {
        $view = $this->view('include-unless');
        $view = $view->with(['greeting' => 'Hello', 'foo' => 'foo']);
        $content = $view->render();
        $this->assertViewContent('', $content);

        $view = $this->view('include-unless');
        $view = $view->with(['greeting' => 'Hello', 'foo' => 'bar']);
        $content = $view->render();
        $this->assertViewContent('Hello Calvin', $content);
    }

    /**
     * @test
     */
    public function include_first_works(): void
    {
        $view = $this->view('include-first');
        $view = $view->with(['greeting' => 'Hello', 'foo' => 'foo']);
        $content = $view->render();
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
        $view = $view->with(['users' => $collection]);
        $content = $view->render();
        $this->assertViewContent('Calvin.John.Jane.', $content);

        $view = $this->view('each');
        $view = $view->with(['users' => []]);
        $content = $view->render();
        $this->assertViewContent('NO USERS', $content);
    }

    /**
     * @test
     */
    public function raw_php_works(): void
    {
        $view = $this->view('raw-php');
        $content = $view->render();
        $this->assertViewContent('10', $content);
    }

    /**
     * @test
     */
    public function service_injection_is_forbidden(): void
    {
        $view = $this->view('service-injection');
        try {
            $view->render();
            $this->fail('@service was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The service directive is not supported. Dont use it. Its evil.',
                ($e->getPrevious()) ? $e->getPrevious()->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function csrf_directive_throws_expection(): void
    {
        $view = $this->view('csrf');
        try {
            $view->render();
            $this->fail('@csrf was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The csrf directive is not supported as it requires the entire laravel framework.',
                ($e->getPrevious()) ? $e->getPrevious()->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function the_method_directive_throws(): void
    {
        $view = $this->view('method');
        try {
            $view->render();
            $this->fail('@method was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The method directive is not supported because form-method spoofing is not supported in WordPress.',
                ($e->getPrevious()) ? $e->getPrevious()->getMessage() : $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function section_directives_work(): void
    {
        $view = $this->view('section-child');
        $content = $view->render();
        $this->assertViewContent('FOOBAZ', $content);
    }

    /**
     * @test
     */
    public function php_files_can_be_rendered(): void
    {
        $view = $this->view('php-file');
        $content = $view->render();
        $this->assertViewContent('PHPONLYFILE', $content);
    }

    private function view(string $view): View
    {
        return $this->view_engine->make('blade-features.' . $view);
    }

}