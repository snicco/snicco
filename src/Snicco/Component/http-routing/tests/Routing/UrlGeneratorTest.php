<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteParameter;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

class UrlGeneratorTest extends HttpRunnerTestCase
{

    protected string $app_domain = 'foobar.com';

    /**
     * @test
     */
    public function absolute_paths_are_used_by_default(): void
    {
        $url = $this->generator()->to('foo');
        $this->assertSame('/foo', $url);
    }

    /**
     * @test
     */
    public function a_valid_absolute_url_will_be_returned_as_is(): void
    {
        $url = $this->generator()->to('https://foobar.com/foo');
        $this->assertSame('https://foobar.com/foo', $url);

        $url = $this->generator()->to('mailto:calvin@web.de');
        $this->assertSame('mailto:calvin@web.de', $url);
    }

    /**
     * @test
     */
    public function absolute_urls_can_be_used(): void
    {
        $url = $this->generator()->to('foo', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertSame('https://foobar.com/foo', $url);
    }

    /**
     * @test
     */
    public function query_arguments_can_be_added(): void
    {
        $url = $this->generator()->to('foo', ['bar' => 'baz']);
        $this->assertSame('/foo?bar=baz', $url);

        $url = $this->generator()->to(
            '/foo',
            ['bar' => 'baz'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $this->assertSame('https://foobar.com/foo?bar=baz', $url);
    }

    /**
     * @test
     */
    public function existing_query_arguments_are_preserved(): void
    {
        $url = $this->generator()->to('foo?boom=bam', ['bar' => 'baz']);
        $this->assertSame('/foo?boom=bam&bar=baz', $url);

        $url = $this->generator()->to(
            'foo?boom=bam',
            ['bar' => 'baz'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $this->assertSame('https://foobar.com/foo?boom=bam&bar=baz', $url);
    }

    /**
     * @test
     */
    public function existing_query_arguments_will_be_url_encoded(): void
    {
        $m = rawurlencode('münchen');
        $expected = '/foo?city=' . $m . '&bar=baz';

        $url = $this->generator()->to('foo?city=münchen', ['bar' => 'baz']);
        $this->assertSame($expected, $url);

        $url = $this->generator()->to(
            'foo?city=münchen',
            ['bar' => 'baz'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $this->assertSame('https://foobar.com' . $expected, $url);
    }

    /**
     * @test
     */
    public function an_existing_fragment_is_preserved(): void
    {
        $url = $this->generator()->to('foo#section1', ['bar' => 'baz']);
        $this->assertSame('/foo?bar=baz#section1', $url);

        $url = $this->generator()->to(
            'foo#section1',
            ['bar' => 'baz'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $this->assertSame('https://foobar.com/foo?bar=baz#section1', $url);
    }

    /**
     * @test
     */
    public function an_existing_fragment_is_url_encoded(): void
    {
        $url = $this->generator()->to('foo#münchen');
        $this->assertSame('/foo#' . rawurlencode('münchen'), $url);
    }

    /**
     * @test
     */
    public function fragments_can_be_added_as_an_argument(): void
    {
        $url = $this->generator()->to('foo', ['bar' => 'baz', '_fragment' => 'section1']);
        $this->assertSame('/foo?bar=baz#section1', $url);

        $url = $this->generator()->to(
            'foo',
            ['bar' => 'baz', '_fragment' => 'section1'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $this->assertSame('https://foobar.com/foo?bar=baz#section1', $url);
    }

    /**
     * @test
     */
    public function an_existing_fragment_is_overwritten_by_a_provided_fragment(): void
    {
        $url = $this->generator()->to('foo#section1', ['_fragment' => 'section2']);
        $this->assertSame('/foo#section2', $url);
    }

    /**
     * @test
     */
    public function the_current_scheme_is_used_if_no_explicit_scheme_is_provided(): void
    {
        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('https://foo.com'),
        );
        $generator = $this->newUrlGenerator($context);

        $url = $generator->to('/foo', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertSame('https://foo.com/foo', $url);

        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('http://foo.com'),
        );
        $generator = $this->newUrlGenerator($context);

        $url = $generator->to('/foo', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertSame('http://foo.com/foo', $url);
    }

    /**
     * @test
     */
    public function the_current_scheme_can_be_overwritten(): void
    {
        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('https://foo.com')
        );
        $generator = $this->newUrlGenerator($context);

        $url = $generator->to('/foo', [], UrlGeneratorInterface::ABSOLUTE_URL, false);
        $this->assertSame('http://foo.com/foo', $url);

        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('http://foo.com')
        );
        $generator = $this->newUrlGenerator($context);

        $url = $generator->to('/foo', [], UrlGeneratorInterface::ABSOLUTE_URL, true);
        $this->assertSame('https://foo.com/foo', $url);
    }

    /**
     * @test
     */
    public function a_scheme_can_be_forced_for_all_generated_urls(): void
    {
        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('http://foo.com'),
            true
        );

        $generator = $this->newUrlGenerator($context);

        $url = $generator->to('/foo', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertSame('https://foo.com/foo', $url);
    }

    /**
     * @test
     */
    public function a_forced_scheme_can_be_explicitly_overwritten(): void
    {
        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('http://foo.com'),
            true
        );

        $generator = $this->newUrlGenerator($context);

        $url = $generator->to('/foo', [], UrlGeneratorInterface::ABSOLUTE_URL, false);
        $this->assertSame('http://foo.com/foo', $url);
    }

    /**
     * @test
     */
    public function a_relative_link_with_a_forced_secure_schema_will_be_absolute_if_the_current_scheme_is_not_secure(
    ): void
    {
        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('http://foo.com')
        );
        $generator = $this->newUrlGenerator($context);

        $url = $generator->to('/foo', [], UrlGeneratorInterface::ABSOLUTE_PATH, true);
        $this->assertSame('https://foo.com/foo', $url);

        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('http://foo.com'),
            true
        );

        $generator = $this->newUrlGenerator($context);
        $url = $generator->to('/foo', [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $this->assertSame('https://foo.com/foo', $url);
    }

    /**
     * @test
     */
    public function a_relative_link_will_not_be_upgraded_to_a_full_url_if_the_request_is_secure(): void
    {
        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('https://foo.com')
        );
        $generator = $this->newUrlGenerator($context);

        $url = $generator->to('/foo', [], UrlGeneratorInterface::ABSOLUTE_PATH, true);
        $this->assertSame('/foo', $url);
    }

    /**
     * @test
     */
    public function if_trailing_slashes_are_used_generated_urls_end_with_a_trailing_trash(): void
    {
        $generator = $this->newUrlGenerator(
            UrlGenerationContext::fromRequest(
                $this->frontendRequest('https://foo.com')
            ),
        );

        $this->assertSame('/foo/', $generator->to('/foo/'));

        $this->assertSame(
            '/foo/?bar=baz#section1',
            $generator->to('/foo/', ['bar' => 'baz', '_fragment' => 'section1'])
        );

        $this->assertSame(
            'https://foo.com/foo/',
            $generator->to('/foo/', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function trailing_slashes_are_not_added_for_urls_that_go_to_a_file(): void
    {
        $generator = $this->newUrlGenerator(null, true);

        $path = '/wp-admin/index.php';
        $url = $this->generator()->to($path);
        $this->assertSame('/wp-admin/index.php', $url);

        $this->assertSame(
            'https://foobar.com/wp-admin/index.php',
            $generator->to($path, [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function query_arguments_will_be_encoded(): void
    {
        $url = $this->generator()->to('/bands', ['s' => 'AC DC']);

        $this->assertSame('/bands?s=AC%20DC', $url);
    }

    /**
     * @test
     */
    public function the_path_will_be_url_encoded(): void
    {
        $m = rawurlencode('münchen');
        $d = rawurlencode('düsseldorf');

        $this->assertSame("/$m/$d", $this->generator()->to('münchen/düsseldorf'));
    }

    /**
     * @test
     */
    public function test_secure(): void
    {
        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('https://foo.com')
        );
        $generator = $this->newUrlGenerator($context);

        $this->assertSame('https://foo.com/foo', $generator->secure('foo'));

        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('http://foo.com')
        );
        $generator = $this->newUrlGenerator($context);

        $this->assertSame(
            'https://foo.com/foo?foo=bar',
            $generator->secure('foo', ['foo' => 'bar'])
        );
    }

    /**
     * @test
     */
    public function test_absolute_url_with_non_standard_https_port(): void
    {
        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('https://foo.com:4000')
        );
        $g = $this->newUrlGenerator($context);

        $this->assertSame('/foo', $g->to('foo'));
        $this->assertSame(
            'https://foo.com:4000/foo',
            $g->to('foo', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function test_absolute_url_with_non_standard_http_port(): void
    {
        $context = UrlGenerationContext::fromRequest(
            $this->frontendRequest('http://foo.com:8080')
        );
        $g = $this->newUrlGenerator($context);

        $this->assertSame('/foo', $g->to('foo'));
        $this->assertSame(
            'http://foo.com:8080/foo',
            $g->to('foo', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function the_previous_url_is_returned_as_is(): void
    {
        $request = $this->frontendRequest('/foo');
        $r1 = $request->withAddedHeader('referer', 'https://other-site.com');
        $r2 = $request->withAddedHeader('referer', '/foo');

        $context = UrlGenerationContext::fromRequest($r1);
        $g = $this->newUrlGenerator($context);

        $this->assertSame('https://other-site.com', $g->previous());

        $context = UrlGenerationContext::fromRequest($r2);
        $g = $this->newUrlGenerator($context);

        $this->assertSame('/foo', $g->previous());
    }

    /**
     * @test
     */
    public function the_fallback_url_is_used_if_no_previous_url_exists(): void
    {
        $this->assertSame('https://foobar.com/fallback', $this->generator()->previous('fallback'));
    }

    /**
     * @test
     */
    public function test_canonical(): void
    {
        $r = $this->frontendRequest('https://foobar.com/foo/bar');

        $g = $this->newUrlGenerator(
            UrlGenerationContext::fromRequest($r)
        );

        $this->assertSame('https://foobar.com/foo/bar', $g->canonical());
    }

    /**
     * @test
     */
    public function the_canonical_stays_url_encoded_and_is_not_double_url_encoded(): void
    {
        $r = $this->frontendRequest('https://foobar.com/münchen');
        $g = $this->newUrlGenerator(
            UrlGenerationContext::fromRequest($r)
        );
        $this->assertSame('https://foobar.com/' . rawurlencode('münchen'), $g->canonical());
    }

    /**
     * @test
     */
    public function test_full(): void
    {
        $r = $this->frontendRequest('http://foobar.com:8080/foo/bar?city=münchen#section1');

        $g = $this->newUrlGenerator(
            UrlGenerationContext::fromRequest($r)
        );

        $this->assertSame(
            'http://foobar.com:8080/foo/bar?city=' . rawurlencode('münchen') . '#section1',
            $g->full()
        );
    }

    /**
     * REVERSE ROUTING
     */

    /**
     * @test
     */
    public function a_route_can_be_named(): void
    {
        $this->routeConfigurator()->get('foo_route', '/foo');
        $this->routeConfigurator()->post('bar_route', '/bar');

        $url = $this->generator()->toRoute('foo_route');
        $this->assertSame('/foo', $url);

        $url = $this->generator()->toRoute('bar_route');
        $this->assertSame('/bar', $url);
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_when_a_route_name_starts_with_a_leading_slash(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->routeConfigurator()->get('/foo_route', '/foo');
    }

    /**
     * @test
     */
    public function an_absolute_url_can_be_created(): void
    {
        $this->routeConfigurator()->get('foo_route', '/foo');

        $url = $this->generator()->toRoute('foo_route', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->assertSame('https://foobar.com/foo', $url);
    }

    /**
     * @test
     */
    public function route_names_are_merged_on_multiple_levels(): void
    {
        $this->routeConfigurator()
            ->name('foo')
            ->group(function () {
                $this->routeConfigurator()->name('bar')->group(function () {
                    $this->routeConfigurator()->get('baz', '/baz');
                });
                $this->routeConfigurator()->get('biz', '/biz');
            });

        $this->assertSame('/baz', $this->generator()->toRoute('foo.bar.baz'));
        $this->assertSame('/biz', $this->generator()->toRoute('foo.biz'));

        $this->expectExceptionMessage('no route with name [foo.bar.biz]');

        $this->assertSame('/baz', $this->generator()->toRoute('foo.bar.biz'));
    }

    /**
     * @test
     */
    public function group_names_are_applied_to_child_routes(): void
    {
        $this->routeConfigurator()
            ->name('foo')
            ->group(function () {
                $this->routeConfigurator()->get('bar', '/bar');
                $this->routeConfigurator()->get('baz', '/baz');
                $this->routeConfigurator()->get('biz', '/biz');
            });

        $this->assertSame('/bar', $this->generator()->toRoute('foo.bar'));
        $this->assertSame('/baz', $this->generator()->toRoute('foo.baz'));
        $this->assertSame('/biz', $this->generator()->toRoute('foo.biz'));
    }

    /**
     * @test
     */
    public function urls_for_routes_with_required_segments_can_be_generated(): void
    {
        $this->routeConfigurator()->get('foo', '/foo/{required}');

        $url = $this->generator()->toRoute('foo', ['required' => 'bar']);
        $this->assertSame('/foo/bar', $url);
    }

    /**
     * @test
     */
    public function urls_for_routes_with_optional_segments_can_be_generated(): void
    {
        $this->routeConfigurator()->get('foo', 'foo/{required}/{optional?}');
        $this->routeConfigurator()->get('bar', 'bar/{required}/{optional?}/');

        $url = $this->generator()->toRoute('foo', [
            'required' => 'bar',
        ]);
        $this->assertSame('/foo/bar', $url);

        $url = $this->generator()->toRoute('bar', [
            'required' => 'baz',
        ]);
        $this->assertSame('/bar/baz/', $url);

        $url = $this->generator()->toRoute('foo', [
            'required' => 'bar',
            'optional' => 'baz',
        ]);
        $this->assertSame('/foo/bar/baz', $url);

        $url = $this->generator()->toRoute('bar', [
            'required' => 'baz',
            'optional' => 'biz',
        ]);
        $this->assertSame('/bar/baz/biz/', $url);
    }

    /**
     * @test
     */
    public function optional_segments_can_be_created_after_fixed_segments(): void
    {
        $this->routeConfigurator()->get('foo', 'foo/{optional?}');
        $url = $this->generator()->toRoute('foo', ['optional' => 'bar']);
        $this->assertSame('/foo/bar', $url);
    }

    /**
     * @test
     */
    public function multiple_optional_segments_can_be_used(): void
    {
        $this->routeConfigurator()->get('foo', 'foo/{opt1?}/{opt2?}/');
        $this->routeConfigurator()->get('bar', 'bar/{required}/{opt1?}/{opt2?}');

        $url = $this->generator()->toRoute('foo', ['opt1' => 'bar', 'opt2' => 'baz']);
        $this->assertSame('/foo/bar/baz/', $url);

        $url = $this->generator()->toRoute('bar', [
            'required' => 'biz',
            'opt1' => 'bar',
            'opt2' => 'baz',
        ]);
        $this->assertSame('/bar/biz/bar/baz', $url);
    }

    /**
     * @test
     */
    public function required_segments_can_be_created_with_regex_constraints(): void
    {
        $this->routeConfigurator()->get('foo', '/foo/{required}')
            ->requirements(['required' => '[a]+']);

        $url = $this->generator()->toRoute('foo', ['required' => 'aaa']);
        $this->assertSame('/foo/aaa', $url);

        $this->expectExceptionMessage(
            'Parameter [required] for route [foo] must match [[a]+] to generate an URL. Given [bbb].'
        );

        $this->generator()->toRoute('foo', ['required' => 'bbb']);
    }

    /**
     * @test
     */
    public function optional_segments_can_be_created_with_regex_constraints(): void
    {
        $this->routeConfigurator()->get('foo', '/foo/{optional?}')
            ->requirements(['optional' => '[a]+']);

        // without param
        $url = $this->generator()->toRoute('foo');
        $this->assertSame('/foo', $url);

        // regex is good
        $url = $this->generator()->toRoute('foo', ['optional' => 'aa']);
        $this->assertSame('/foo/aa', $url);

        // regex is bad
        $this->expectExceptionMessage(
            'Parameter [optional] for route [foo] must match [[a]+] to generate an URL. Given [bb].'
        );

        $this->generator()->toRoute('foo', ['optional' => 'bb']);
    }

    /**
     * @test
     */
    public function required_and_optional_segments_can_be_created_with_regex(): void
    {
        $this->routeConfigurator()->get('foo', '/foo/{required}/{optional?}')
            ->requirements([
                'required' => '\w+',
                'optional' => '\w+',
            ]);

        $this->routeConfigurator()->get('bar', '/bar/{required}/{optional?}')
            ->requirements(['required' => '\w+', 'optional' => '\w+']);

        $this->routeConfigurator()->get('foobar', '/baz/{required}/{optional1?}/{optional2?}')
            ->requirements([
                'required' => '\w+',
                'optional1' => '\w+',
                'optional2' => '\w+',
            ]);

        $url = $this->generator()->toRoute('foo', ['required' => 'bar']);
        $this->assertSame('/foo/bar', $url);

        $url = $this->generator()->toRoute('bar', [
            'required' => 'baz',
            'optional' => 'biz',
        ]);
        $this->assertSame('/bar/baz/biz', $url);

        $url = $this->generator()->toRoute('foobar', [
            'required' => 'bar',
            'optional1' => 'boo',
            'optional2' => 'biz',
        ]);
        $this->assertSame('/baz/bar/boo/biz', $url);
    }

    /**
     * @test
     */
    public function missing_required_arguments_throw_an_exception(): void
    {
        $this->expectExceptionMessage('Required parameter [required] is missing for route [foo].');

        $this->routeConfigurator()->get('foo', 'foo/{required}');

        $this->generator()->toRoute('foo');
    }

    /**
     * @test
     */
    public function exceptions_are_thrown_for_missing_route_names(): void
    {
        $this->expectException(RouteNotFound::class);
        $this->generator()->toRoute('foo');
    }

    /**
     * @test
     */
    public function a_route_is_replaced_if_another_route_with_the_same_name_is_added(): void
    {
        $this->routeConfigurator()->get('route1', 'foo');
        $this->routeConfigurator()->get('route1', 'bar');

        $url = $this->generator()->toRoute('route1');
        $this->assertSame('/bar', $url);
    }

    /**
     * @test
     */
    public function generated_urls_are_cached_if_no_route_arguments_are_required(): void
    {
        $this->routeConfigurator()->get('static', '/static');
        $this->routeConfigurator()->get('foo', '/foo/{required}');

        $url = $this->generator()->toRoute('static');
        $this->assertSame('/static', $url);

        $url = $this->generator()->toRoute('static');
        $this->assertSame('/static', $url);

        $url = $this->generator()->toRoute('foo', ['required' => 'bar']);
        $this->assertSame('/foo/bar', $url);

        $url = $this->generator()->toRoute('foo', ['required' => 'baz']);
        $this->assertSame('/foo/baz', $url);
    }

    /**
     * @test
     */
    public function test_with_complex_reqex(): void
    {
        $this->routeConfigurator()
            ->get('teams', '/teams/{team}/{player?}')
            ->requirements([
                'team' => 'm{1}.+united[xy]',
                'player' => 'a{2,}calvin',
            ]);

        $url = $this->generator()->toRoute('teams', [
            'team' => 'manchesterunitedx',
            'player' => 'aacalvin',
        ]);
        $this->assertSame('/teams/manchesterunitedx/aacalvin', $url);

        // Fails because not starting with m.
        try {
            $this->generator()->toRoute('teams', [
                'team' => 'lanchesterunited',
                'player' => 'aacalvin',
            ]);
            $this->fail('Invalid constraint created a route.');
        } catch (BadRouteParameter $e) {
            $this->assertStringContainsString(
                'Parameter [team] for route [teams] must match [m{1}.+united[xy]] to generate an URL. Given [lanchesterunited].',
                $e->getMessage()
            );
        }

        // Fails because not using united.
        try {
            $this->generator()->toRoute('teams', [
                'team' => 'manchestercityx',
                'player' => 'aacalvin',
            ]);
            $this->fail('Invalid constraint created a route.');
        } catch (BadRouteParameter $e) {
            $this->assertStringContainsString(
                'Parameter [team] for route [teams] must match [m{1}.+united[xy]] to generate an URL. Given [manchestercityx].',
                $e->getMessage()
            );
        }

        // Fails because not using x or y at the end.
        try {
            $this->generator()->toRoute('teams', [
                'team' => 'manchesterunitedz',
                'player' => 'aacalvin',
            ]);
            $this->fail('Invalid constraint created a route.');
        } catch (BadRouteParameter $e) {
            $this->assertStringContainsString(
                'Parameter [team] for route [teams] must match [m{1}.+united[xy]] to generate an URL. Given [manchesterunitedz].',
                $e->getMessage()
            );
        }

        // Fails because optional parameter is present but doesn't match regex, uses only one a
        try {
            $this->generator()->toRoute('teams', [
                'team' => 'manchesterunitedx',
                'player' => 'acalvin',
            ]);
            $this->fail('Invalid constraint created a route.');
        } catch (BadRouteParameter $e) {
            $this->assertStringContainsString(
                'Parameter [player] for route [teams] must match [a{2,}calvin] to generate an URL. Given [acalvin].',
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function additional_parameters_are_added_as_query_arguments(): void
    {
        $this->routeConfigurator()->get('teams', '/{team}/{player}');

        $url = $this->generator()->toRoute(
            'teams',
            [
                'team' => 'bayernmünchen',
                'player' => 'calvin alkan',
                'foo' => 'bar',
                'baz' => 'biz',
                '_fragment' => 'section1',
            ]
        );

        $expected = '/'
            . rawurlencode('bayernmünchen')
            . '/'
            . rawurlencode('calvin alkan')
            . '?foo=bar&baz=biz#section1';

        $this->assertSame($expected, $url);
    }

    /**
     * @test
     */
    public function test_toLogin_with_named_login_route(): void
    {
        $this->routeConfigurator()->get('login', '/login');

        $this->assertSame('/login?foo=bar', $this->generator()->toLogin(['foo' => 'bar']));
        $this->assertSame(
            'https://foobar.com/login?foo=bar',
            $this->generator()->toLogin(['foo' => 'bar'], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function test_toLogin_with_named_auth_login_route(): void
    {
        $this->routeConfigurator()->get('auth.login', '/login');

        $this->assertSame('/login?foo=bar', $this->generator()->toLogin(['foo' => 'bar']));
        $this->assertSame(
            'https://foobar.com/login?foo=bar',
            $this->generator()->toLogin(['foo' => 'bar'], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function test_two_login_with_named_framework_auth_login_route(): void
    {
        $this->routeConfigurator()->get('framework.auth.login', '/login');

        $this->assertSame('/login?foo=bar', $this->generator()->toLogin(['foo' => 'bar']));
        $this->assertSame(
            'https://foobar.com/login?foo=bar',
            $this->generator()->toLogin(['foo' => 'bar'], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function if_no_login_route_matches_the_admin_dashboard_default_is_used(): void
    {
        $this->assertSame('/wp-login.php?foo=bar', $this->generator()->toLogin(['foo' => 'bar']));
        $this->assertSame(
            'https://foobar.com/wp-login.php?foo=bar',
            $this->generator()->toLogin(['foo' => 'bar'], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_for_non_string_or_int_replacement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Replacements must be string or integer. Got [array].');

        $this->routeConfigurator()->get('foo', 'foo/{required}');

        $this->generator()->toRoute('foo', ['required' => []]);
    }

}
