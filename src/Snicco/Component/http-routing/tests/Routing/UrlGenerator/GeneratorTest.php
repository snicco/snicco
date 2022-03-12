<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlGenerator;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteParameter;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @internal
 */
final class GeneratorTest extends HttpRunnerTestCase
{
    protected string $app_domain = 'foobar.com';

    /**
     * @test
     */
    public function absolute_paths_are_used_by_default(): void
    {
        $url = $this->generator()
            ->to('foo');
        $this->assertSame('/foo', $url);
    }

    /**
     * @test
     */
    public function a_valid_absolute_url_will_be_returned_as_is(): void
    {
        $url = $this->generator()
            ->to('https://foobar.com/foo');
        $this->assertSame('https://foobar.com/foo', $url);

        $url = $this->generator()
            ->to('mailto:calvin@web.de');
        $this->assertSame('mailto:calvin@web.de', $url);
    }

    /**
     * @test
     */
    public function absolute_urls_can_be_used(): void
    {
        $url = $this->generator()
            ->to('foo', [], UrlGenerator::ABSOLUTE_URL);
        $this->assertSame('https://foobar.com/foo', $url);
    }

    /**
     * @test
     */
    public function query_arguments_can_be_added(): void
    {
        $url = $this->generator()
            ->to('foo', [
                'bar' => 'baz',
            ]);
        $this->assertSame('/foo?bar=baz', $url);

        $url = $this->generator()
            ->to('/foo', [
                'bar' => 'baz',
            ], UrlGenerator::ABSOLUTE_URL);
        $this->assertSame('https://foobar.com/foo?bar=baz', $url);
    }

    /**
     * @test
     */
    public function existing_query_arguments_are_preserved(): void
    {
        $url = $this->generator()
            ->to('foo?boom=bam', [
                'bar' => 'baz',
            ]);
        $this->assertSame('/foo?boom=bam&bar=baz', $url);

        $url = $this->generator()
            ->to('foo?boom=bam', [
                'bar' => 'baz',
            ], UrlGenerator::ABSOLUTE_URL);
        $this->assertSame('https://foobar.com/foo?boom=bam&bar=baz', $url);

        $url = $this->generator()
            ->to('foo?', [
                'bar' => 'baz',
            ]);
        $this->assertSame('/foo?bar=baz', $url);
    }

    /**
     * @test
     */
    public function existing_query_arguments_will_be_url_encoded(): void
    {
        $m = rawurlencode('münchen');
        $expected = '/foo?city=' . $m . '&bar=baz';

        $url = $this->generator()
            ->to('foo?city=münchen', [
                'bar' => 'baz',
            ]);
        $this->assertSame($expected, $url);

        $url = $this->generator()
            ->to('foo?city=münchen', [
                'bar' => 'baz',
            ], UrlGenerator::ABSOLUTE_URL);
        $this->assertSame('https://foobar.com' . $expected, $url);
    }

    /**
     * @test
     */
    public function an_existing_fragment_is_preserved(): void
    {
        $url = $this->generator()
            ->to('foo#section1', [
                'bar' => 'baz',
            ]);
        $this->assertSame('/foo?bar=baz#section1', $url);

        $url = $this->generator()
            ->to('foo#section1', [
                'bar' => 'baz',
            ], UrlGenerator::ABSOLUTE_URL);
        $this->assertSame('https://foobar.com/foo?bar=baz#section1', $url);
    }

    /**
     * @test
     */
    public function an_existing_fragment_is_url_encoded(): void
    {
        $url = $this->generator()
            ->to('foo#münchen');
        $this->assertSame('/foo#' . rawurlencode('münchen'), $url);
    }

    /**
     * @test
     */
    public function fragments_can_be_added_as_an_argument(): void
    {
        $url = $this->generator()
            ->to('foo', [
                'bar' => 'baz',
                '_fragment' => 'section1',
            ]);
        $this->assertSame('/foo?bar=baz#section1', $url);

        $url = $this->generator()
            ->to('foo', [
                'bar' => 'baz',
                '_fragment' => 'section1',
            ], UrlGenerator::ABSOLUTE_URL);
        $this->assertSame('https://foobar.com/foo?bar=baz#section1', $url);
    }

    /**
     * @test
     */
    public function an_existing_fragment_is_overwritten_by_a_provided_fragment(): void
    {
        $url = $this->generator()
            ->to('foo#section1', [
                '_fragment' => 'section2',
            ]);
        $this->assertSame('/foo#section2', $url);
    }

    /**
     * @test
     */
    public function urls_with_use_the_default_settings_of_the_url_generation_context(): void
    {
        $context = new UrlGenerationContext('foo.com');
        $generator = $this->generator($context);

        $url = $generator->to('/foo', [], UrlGenerator::ABSOLUTE_URL);
        $this->assertSame('https://foo.com/foo', $url);

        $url = $generator->to('/foo', [], UrlGenerator::ABSOLUTE_URL, false);
        $this->assertSame('http://foo.com/foo', $url);

        $context = new UrlGenerationContext('foo.com', 443, 80, false);
        $generator = $this->generator($context);

        $url = $generator->to('/foo', [], UrlGenerator::ABSOLUTE_URL);
        $this->assertSame('http://foo.com/foo', $url);

        $url = $generator->to('/foo', [], UrlGenerator::ABSOLUTE_URL, true);
        $this->assertSame('https://foo.com/foo', $url);
    }

    /**
     * @test
     */
    public function query_arguments_will_be_encoded(): void
    {
        $url = $this->generator()
            ->to('/bands', [
                's' => 'AC DC',
            ]);

        $this->assertSame('/bands?s=AC%20DC', $url);
    }

    /**
     * @test
     */
    public function the_path_will_be_url_encoded(): void
    {
        $m = rawurlencode('münchen');
        $d = rawurlencode('düsseldorf');

        $this->assertSame(sprintf('/%s/%s', $m, $d), $this->generator()->to('münchen/düsseldorf'));
    }

    /**
     * @test
     */
    public function test_absolute_url_with_non_standard_https_port(): void
    {
        $context = new UrlGenerationContext('foo.com', 4000);
        $g = $this->generator($context);

        $this->assertSame('/foo', $g->to('foo'));
        $this->assertSame('https://foo.com:4000/foo', $g->to('foo', [], UrlGenerator::ABSOLUTE_URL));
    }

    /**
     * @test
     */
    public function test_absolute_url_with_non_standard_http_port(): void
    {
        $context = new UrlGenerationContext('foo.com', 443, 8080);
        $g = $this->generator($context);

        $this->assertSame('/foo', $g->to('foo'));
        $this->assertSame('http://foo.com:8080/foo', $g->to('foo', [], UrlGenerator::ABSOLUTE_URL, false));
    }

    /**
     * REVERSE ROUTING.
     */

    /**
     * @test
     */
    public function a_route_can_be_named(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo_route', '/foo');
            $configurator->post('bar_route', '/bar');
        });

        $url = $routing->urlGenerator()
            ->toRoute('foo_route');
        $this->assertSame('/foo', $url);

        $url = $routing->urlGenerator()
            ->toRoute('bar_route');
        $this->assertSame('/bar', $url);
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_when_a_route_name_starts_with_a_leading_slash(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('/foo_route', '/foo');
        });
    }

    /**
     * @test
     */
    public function an_absolute_url_can_be_created(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo_route', '/foo');
        });

        $url = $routing->urlGenerator()
            ->toRoute('foo_route', [], UrlGenerator::ABSOLUTE_URL);
        $this->assertSame('https://foobar.com/foo', $url);
    }

    /**
     * @test
     */
    public function route_names_are_merged_on_multiple_levels(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->name('foo')
                ->group(function (WebRoutingConfigurator $configurator): void {
                    $configurator->name('bar')
                        ->group(function (WebRoutingConfigurator $configurator): void {
                            $configurator->get('baz', '/baz');
                        });
                    $configurator->get('biz', '/biz');
                });
        });

        $this->assertSame('/baz', $routing->urlGenerator()->toRoute('foo.bar.baz'));
        $this->assertSame('/biz', $routing->urlGenerator()->toRoute('foo.biz'));

        $this->expectExceptionMessage('no route with name [foo.bar.biz]');

        $this->assertSame('/baz', $routing->urlGenerator()->toRoute('foo.bar.biz'));
    }

    /**
     * @test
     */
    public function group_names_are_applied_to_child_routes(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->name('foo')
                ->group(function (WebRoutingConfigurator $configurator): void {
                    $configurator->get('bar', '/bar');
                    $configurator->get('baz', '/baz');
                    $configurator->get('biz', '/biz');
                });
        });

        $this->assertSame('/bar', $routing->urlGenerator()->toRoute('foo.bar'));
        $this->assertSame('/baz', $routing->urlGenerator()->toRoute('foo.baz'));
        $this->assertSame('/biz', $routing->urlGenerator()->toRoute('foo.biz'));
    }

    /**
     * @test
     */
    public function urls_for_routes_with_required_segments_can_be_generated(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo/{required}');
        });

        $url = $routing->urlGenerator()
            ->toRoute('foo', [
                'required' => 'bar',
            ]);
        $this->assertSame('/foo/bar', $url);
    }

    /**
     * @test
     */
    public function urls_for_routes_with_optional_segments_can_be_generated(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', 'foo/{required}/{optional?}');
            $configurator->get('bar', 'bar/{required}/{optional?}/');
        });

        $url = $routing->urlGenerator()
            ->toRoute('foo', [
                'required' => 'bar',
            ]);
        $this->assertSame('/foo/bar', $url);

        $url = $routing->urlGenerator()
            ->toRoute('bar', [
                'required' => 'baz',
            ]);
        $this->assertSame('/bar/baz/', $url);

        $url = $routing->urlGenerator()
            ->toRoute('foo', [
                'required' => 'bar',
                'optional' => 'baz',
            ]);
        $this->assertSame('/foo/bar/baz', $url);

        $url = $routing->urlGenerator()
            ->toRoute('bar', [
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
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', 'foo/{optional?}');
        });
        $url = $routing->urlGenerator()
            ->toRoute('foo', [
                'optional' => 'bar',
            ]);
        $this->assertSame('/foo/bar', $url);
    }

    /**
     * @test
     */
    public function multiple_optional_segments_can_be_used(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', 'foo/{opt1?}/{opt2?}/');
            $configurator->get('bar', 'bar/{required}/{opt1?}/{opt2?}');
        });

        $url = $routing->urlGenerator()
            ->toRoute('foo', [
                'opt1' => 'bar',
                'opt2' => 'baz',
            ]);
        $this->assertSame('/foo/bar/baz/', $url);

        $url = $routing->urlGenerator()
            ->toRoute('bar', [
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
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo/{required}')
                ->requirements([
                    'required' => '[a]+',
                ]);
        });

        $url = $routing->urlGenerator()
            ->toRoute('foo', [
                'required' => 'aaa',
            ]);
        $this->assertSame('/foo/aaa', $url);

        $this->expectExceptionMessage(
            'Parameter [required] for route [foo] must match [[a]+] to generate an URL. Given [bbb].'
        );

        $routing->urlGenerator()
            ->toRoute('foo', [
                'required' => 'bbb',
            ]);
    }

    /**
     * @test
     */
    public function optional_segments_can_be_created_with_regex_constraints(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo/{optional?}')
                ->requirements([
                    'optional' => '[a]+',
                ]);
        });

        // without param
        $url = $routing->urlGenerator()
            ->toRoute('foo');
        $this->assertSame('/foo', $url);

        // regex is good
        $url = $routing->urlGenerator()
            ->toRoute('foo', [
                'optional' => 'aa',
            ]);
        $this->assertSame('/foo/aa', $url);

        // regex is bad
        $this->expectExceptionMessage(
            'Parameter [optional] for route [foo] must match [[a]+] to generate an URL. Given [bb].'
        );

        $routing->urlGenerator()
            ->toRoute('foo', [
                'optional' => 'bb',
            ]);
    }

    /**
     * @test
     */
    public function required_and_optional_segments_can_be_created_with_regex(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo/{required}/{optional?}')
                ->requirements([
                    'required' => '\w+',
                    'optional' => '\w+',
                ]);

            $configurator->get('bar', '/bar/{required}/{optional?}')
                ->requirements([
                    'required' => '\w+',
                    'optional' => '\w+',
                ]);

            $configurator->get('foobar', '/baz/{required}/{optional1?}/{optional2?}')
                ->requirements([
                    'required' => '\w+',
                    'optional1' => '\w+',
                    'optional2' => '\w+',
                ]);
        });

        $url = $routing->urlGenerator()
            ->toRoute('foo', [
                'required' => 'bar',
            ]);
        $this->assertSame('/foo/bar', $url);

        $url = $routing->urlGenerator()
            ->toRoute('bar', [
                'required' => 'baz',
                'optional' => 'biz',
            ]);
        $this->assertSame('/bar/baz/biz', $url);

        $url = $routing->urlGenerator()
            ->toRoute('foobar', [
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

        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', 'foo/{required}');
        });

        $routing->urlGenerator()
            ->toRoute('foo');
    }

    /**
     * @test
     */
    public function exceptions_are_thrown_for_missing_route_names(): void
    {
        $this->expectException(RouteNotFound::class);
        $this->generator()
            ->toRoute('foo');
    }

    /**
     * @test
     */
    public function a_route_is_replaced_if_another_route_with_the_same_name_is_added(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('route1', 'foo');
            $configurator->get('route1', 'bar');
        });

        $url = $routing->urlGenerator()
            ->toRoute('route1');
        $this->assertSame('/bar', $url);
    }

    /**
     * @test
     */
    public function generated_urls_are_cached_if_no_route_arguments_are_required(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('static', '/static');
            $configurator->get('foo', '/foo/{required}');
        });

        $url = $routing->urlGenerator()
            ->toRoute('static');
        $this->assertSame('/static', $url);

        $url = $routing->urlGenerator()
            ->toRoute('static');
        $this->assertSame('/static', $url);

        $url = $routing->urlGenerator()
            ->toRoute('foo', [
                'required' => 'bar',
            ]);
        $this->assertSame('/foo/bar', $url);

        $url = $routing->urlGenerator()
            ->toRoute('foo', [
                'required' => 'baz',
            ]);
        $this->assertSame('/foo/baz', $url);
    }

    /**
     * @test
     */
    public function test_with_complex_reqex(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('teams', '/teams/{team}/{player?}')
                ->requirements([
                    'team' => 'm{1}.+united[xy]',
                    'player' => 'a{2,}calvin',
                ]);
        });

        $url = $routing->urlGenerator()
            ->toRoute('teams', [
                'team' => 'manchesterunitedx',
                'player' => 'aacalvin',
            ]);
        $this->assertSame('/teams/manchesterunitedx/aacalvin', $url);

        // Fails because not starting with m.
        try {
            $routing->urlGenerator()
                ->toRoute('teams', [
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
            $routing->urlGenerator()
                ->toRoute('teams', [
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
            $routing->urlGenerator()
                ->toRoute('teams', [
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
            $routing->urlGenerator()
                ->toRoute('teams', [
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
    public function test_with_optional_segments_where_no_value_is_provided(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/foo/{bar?}/{baz?}');
        });

        $url = $routing->urlGenerator()
            ->toRoute('r1', [
                'param1' => 'foo',
            ]);

        $this->assertSame('/foo?param1=foo', $url);
    }

    /**
     * @test
     */
    public function additional_parameters_are_added_as_query_arguments(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('teams', '/{team}/{player}');
        });

        $url = $routing->urlGenerator()
            ->toRoute(
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
    public function test_to_login_with_named_login_route(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('login', '/login1');
            $configurator->get('auth.login', '/login2');
            $configurator->get('framework.auth.login', '/login3');
        });

        $this->assertSame('/login1?foo=bar', $routing->urlGenerator()->toLogin([
            'foo' => 'bar',
        ]));
        $this->assertSame(
            'https://foobar.com/login1?foo=bar',
            $routing->urlGenerator()
                ->toLogin([
                    'foo' => 'bar',
                ], UrlGenerator::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function test_to_login_with_named_auth_login_route(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('auth.login', '/login2');
            $configurator->get('framework.auth.login', '/login3');
        });

        $this->assertSame('/login2?foo=bar', $routing->urlGenerator()->toLogin([
            'foo' => 'bar',
        ]));
        $this->assertSame(
            'https://foobar.com/login2?foo=bar',
            $routing->urlGenerator()
                ->toLogin([
                    'foo' => 'bar',
                ], UrlGenerator::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function test_two_login_with_named_framework_auth_login_route(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('framework.auth.login', '/login3');
        });

        $g = $routing->urlGenerator();

        $this->assertSame('/login3?foo=bar', $g->toLogin([
            'foo' => 'bar',
        ]));
        $this->assertSame(
            'https://foobar.com/login3?foo=bar',
            $g->toLogin([
                'foo' => 'bar',
            ], UrlGenerator::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     */
    public function if_no_login_route_matches_the_admin_dashboard_default_is_used(): void
    {
        $this->assertSame('/wp-login.php?foo=bar', $this->generator()->toLogin([
            'foo' => 'bar',
        ]));
        $this->assertSame(
            'https://foobar.com/wp-login.php?foo=bar',
            $this->generator()
                ->toLogin([
                    'foo' => 'bar',
                ], UrlGenerator::ABSOLUTE_URL)
        );
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function an_exception_is_thrown_for_non_string_or_int_replacement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Replacements must be string or integer. Got [array].');

        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', 'foo/{required}');
        });

        $routing->urlGenerator()
            ->toRoute('foo', [
                'required' => [],
            ]);
    }
}
