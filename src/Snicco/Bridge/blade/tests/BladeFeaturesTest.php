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

        $this->assertStringStartsWith('&lt;script', $view->toString());
    }

    private function view(string $view): View
    {
        return $this->view_engine->make('blade-features.' . $view);
    }

    /**
     * @test
     */
    public function xss_encoding_can_be_disabled(): void
    {
        $view = $this->view('xss-disabled')
            ->with('script', '<script type="text/javascript">alert("Hacked!");</script>');

        $this->assertStringStartsWith('<script', $view->toString());
    }

    /**
     * @test
     */
    public function json_works(): void
    {
        $view = $this->view('json')->with('json', ['foo' => 'bar']);
        $content = $view->toString();
        $this->assertSame(['foo' => 'bar'], json_decode($content, true));
    }

    /**
     * @test
     */
    public function if_works(): void
    {
        $view = $this->view('if')->with('records', ['foo']);
        $content = $view->toString();
        $this->assertViewContent('I have one record!', $content);

        $view = $this->view('if')->with('records', ['foo', 'bar']);
        $content = $view->toString();
        $this->assertViewContent('I have multiple records!', $content);

        $view = $this->view('if')->with('records', []);
        $content = $view->toString();
        $this->assertViewContent("I don't have any records!", $content);
    }

    /**
     * @test
     */
    public function unless_works(): void
    {
        $view = $this->view('unless')->with('foo', 'foo');
        $content = $view->toString();
        $this->assertViewContent('', $content);

        $view = $this->view('unless')->with('foo', 'bar');
        $content = $view->toString();
        $this->assertViewContent('UNLESS', $content);
    }

    /**
     * @test
     */
    public function empty_isset_works(): void
    {
        $view = $this->view('isset-empty')->with('isset', 'foo')->with('empty', 'blabla');
        $content = $view->toString();
        $this->assertViewContent('ISSET', $content);

        $view = $this->view('isset-empty')->with('empty', '');
        $content = $view->toString();
        $this->assertViewContent('EMPTY', $content);
    }

    /**
     * @test
     */
    public function including_a_subview_works(): void
    {
        $view = $this->view('parent')->with('greeting', 'Hello');
        $content = $view->toString();
        $this->assertViewContent('Hello calvin', $content);
    }

    /**
     * @test
     */
    public function include_if_works(): void
    {
        $view = $this->view('include-if')->with('greeting', 'Hello');
        $content = $view->toString();
        $this->assertViewContent('Hello calvin', $content);

        $view = $this->view('include-if-bogus')->with('greeting', 'Hello');
        $content = $view->toString();
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function include_when_works(): void
    {
        $view = $this->view('include-when')->with(['greeting' => 'Hello', 'foo' => 'foo']);
        $content = $view->toString();
        $this->assertViewContent('Hello calvin', $content);

        $view = $this->view('include-when')->with(['greeting' => 'Hello', 'foo' => 'bogus']);
        $content = $view->toString();
        $this->assertViewContent('', $content);
    }

    /**
     * @test
     */
    public function include_unless_works(): void
    {
        $view = $this->view('include-unless')->with(['greeting' => 'Hello', 'foo' => 'foo']);
        $content = $view->toString();
        $this->assertViewContent('', $content);

        $view = $this->view('include-unless')->with(['greeting' => 'Hello', 'foo' => 'bar']);
        $content = $view->toString();
        $this->assertViewContent('Hello Calvin', $content);
    }

    /**
     * @test
     */
    public function include_first_works(): void
    {
        $view = $this->view('include-first')->with(['greeting' => 'Hello', 'foo' => 'foo']);
        $content = $view->toString();
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

        $view = $this->view('each')->with(['users' => $collection]);
        $content = $view->toString();
        $this->assertViewContent('Calvin.John.Jane.', $content);

        $view = $this->view('each')->with(['users' => []]);
        $content = $view->toString();
        $this->assertViewContent('NO USERS', $content);
    }

    /**
     * @test
     */
    public function raw_php_works(): void
    {
        $view = $this->view('raw-php');
        $content = $view->toString();
        $this->assertViewContent('10', $content);
    }

    /**
     * @test
     */
    public function service_injection_is_forbidden(): void
    {
        $view = $this->view('service-injection');
        try {
            $view->toString();
            $this->fail('@service was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The service directive is not supported. Dont use it. Its evil.',
                $e->getPrevious()->getMessage()
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
            $view->toString();
            $this->fail('@csrf was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The csrf directive is not supported as it requires the entire laravel framework.',
                $e->getPrevious()->getMessage()
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
            $view->toString();
            $this->fail('@method was allowed.');
        } catch (ViewCantBeRendered $e) {
            $this->assertStringStartsWith(
                'The method directive is not supported because form-method spoofing is not supported in WordPress.',
                $e->getPrevious()->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function section_directives_work(): void
    {
        $view = $this->view('section-child');
        $content = $view->toString();
        $this->assertViewContent('FOOBAZ', $content);
    }

    /**
     * @test
     */
    public function php_files_can_be_rendered(): void
    {
        $view = $this->view('php-file');
        $content = $view->toString();
        $this->assertViewContent('PHPONLYFILE', $content);
    }

}