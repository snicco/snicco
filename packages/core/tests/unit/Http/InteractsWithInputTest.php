<?php

declare(strict_types=1);

namespace Tests\Core\unit\Http;

use Mockery;
use RuntimeException;
use Snicco\Support\WP;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Request;
use Snicco\Validation\Validator;
use Respect\Validation\Validator as v;
use Tests\Codeception\shared\UnitTest;
use Snicco\Session\Drivers\ArraySessionDriver;
use Tests\Core\fixtures\TestDoubles\TestRequest;
use Snicco\Validation\Exceptions\ValidationException;

class InteractsWithInputTest extends UnitTest
{
    
    private Request $request;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->request = TestRequest::from('GET', 'foo');
    }
    
    public function testGetFromServer()
    {
        $request = TestRequest::withServerParams($this->request, ['foo' => 'bar']);
        
        $this->assertSame('bar', $request->server('foo'));
        
        $this->assertSame('default', $request->server('bogus', 'default'));
    }
    
    public function testInputDoesNotDependOnVerb()
    {
        $request = $this->request->withQueryParams(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $request->all());
        
        $request = TestRequest::from('POST', '/foo')->withParsedBody(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $request->all());
    }
    
    public function testInputWithKey()
    {
        $request = $this->request->withQueryParams(
            [
                'foo' => 'bar',
                'baz' => 'biz',
                'team' => [
                    'player' => 'calvin',
                ],
            ]
        
        );
        $this->assertSame('bar', $request->input('foo'));
        $this->assertSame('default', $request->input('bogus', 'default'));
        $this->assertSame('calvin', $request->input('team.player'));
    }
    
    public function testInputNested()
    {
        $request = $this->request->withQueryParams([
            'products' => [
                [
                    'name' => 'shoe',
                    'price' => '10',
                ],
                [
                    'name' => 'shirt',
                    'price' => '25',
                ],
            ],
        ]);
        
        $name = $request->input('products.0.name');
        $this->assertSame('shoe', $name);
        
        $names = $request->input('products.*.name');
        $this->assertSame(['shoe', 'shirt'], $names);
    }
    
    public function testInputIsAliasForAll()
    {
        $request = $this->request->withQueryParams(
            [
                'foo' => 'bar',
                'baz' => 'biz',
                'team' => [
                    'player' => 'calvin',
                ],
            ]
        
        );
        
        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'biz',
            'team' => [
                'player' => 'calvin',
            ],
        ], $request->input());
    }
    
    public function testQuery()
    {
        $request = $this->request->withQueryParams(['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $request->query());
        $this->assertSame('bar', $request->query('foo'));
        $this->assertSame('default', $request->query('bogus', 'default'));
        
        $request = TestRequest::from('POST', '/foo')->withParsedBody(['foo' => 'bar']);
        $this->assertSame(null, $request->query('foo'));
    }
    
    public function testQueryString()
    {
        $request = TestRequest::fromFullUrl('GET', 'https://foobar.com?foo=bar&baz=biz&=');
        
        $this->assertSame('foo=bar&baz=biz', $request->queryString());
    }
    
    public function testBoolean()
    {
        $request = $this->request->withQueryParams([
            'foo' => 1,
            'bar' => '1',
            'baz' => 'on',
            'biz' => 'yes',
            'boo' => true,
            'bam' => 'true',
            'bogus' => 'bogus',
        ]);
        
        $this->assertTrue($request->boolean('foo'));
        $this->assertTrue($request->boolean('bar'));
        $this->assertTrue($request->boolean('baz'));
        $this->assertTrue($request->boolean('biz'));
        $this->assertTrue($request->boolean('boo'));
        $this->assertTrue($request->boolean('bam'));
        $this->assertFalse($request->boolean('bogus'));
    }
    
    public function testOnly()
    {
        $request = $this->request->withQueryParams([
            'product' => [
                'name' => 'shoe',
                'price' => '10',
            
            ],
        ]);
        
        $only = $request->only('product.name');
        $expected = [
            'product' => [
                'name' => 'shoe',
            
            ],
        ];
        $this->assertSame($expected, $only);
        
        $only = $request->only('product.description');
        $expected = [];
        $this->assertSame($expected, $only);
    }
    
    public function testExcept()
    {
        $request = $this->request->withQueryParams([
            'product' => [
                'name' => 'shoe',
                'price' => '10',
            ],
            'merchant' => [
                'name' => 'calvin',
                'age' => '23',
            ],
        ]);
        
        $input = $request->except('product.name', 'merchant');
        
        $expected = [
            'product' => [
                'price' => '10',
            ],
        ];
        
        $this->assertSame($expected, $input);
    }
    
    public function testHas()
    {
        $request = $this->request->withQueryParams([
            'products' => [
                [
                    'name' => 'shoe',
                    'price' => '10',
                ],
                [
                    'name' => 'shirt',
                    'price' => '25',
                ],
            ],
            'dev' => 'calvin',
        ]);
        
        $this->assertTrue($request->has('products'));
        $this->assertTrue($request->has('products.0.name'));
        $this->assertTrue($request->has('products.0.name', 'products.0.price'));
        $this->assertTrue($request->has('products.0.name', 'products.0.price', 'dev'));
        $this->assertFalse(
            $request->has('products.0.name', 'products.0.price', 'products.0.label')
        );
    }
    
    public function testHasAny()
    {
        $request =
            $this->request->withQueryParams(['name' => 'calvin', 'age' => '', 'city' => null]);
        $this->assertTrue($request->hasAny('name'));
        $this->assertTrue($request->hasAny('age'));
        $this->assertTrue($request->hasAny('city'));
        $this->assertTrue($request->hasAny('name', 'email'));
        $this->assertTrue($request->hasAny(['name', 'email']));
        $this->assertFalse($request->hasAny('foo'));
        
        $request = $this->request->withQueryParams(['name' => 'calvin', 'email' => 'foo']);
        $this->assertTrue($request->hasAny('name', 'email'));
        $this->assertFalse($request->hasAny('surname', 'password'));
        $this->assertFalse($request->hasAny(['surname', 'password']));
        
        $request = $this->request->withQueryParams(['foo' => ['bar' => null, 'baz' => '']]);
        $this->assertTrue($request->hasAny('foo.bar'));
        $this->assertTrue($request->hasAny('foo.baz'));
        $this->assertFalse($request->hasAny('foo.bax'));
        $this->assertTrue($request->hasAny(['foo.bax', 'foo.baz']));
    }
    
    public function testFilled()
    {
        $request = $this->request->withQueryParams([
            'dev' => 'calvin',
            'foo' => '',
        ]);
        
        $this->assertTrue($request->filled('dev'));
        $this->assertFalse($request->filled('foo'));
    }
    
    public function testMissing()
    {
        $request =
            $this->request->withQueryParams(['name' => 'calvin', 'age' => '', 'city' => null]);
        $this->assertFalse($request->missing('name'));
        $this->assertFalse($request->missing('age'));
        $this->assertFalse($request->missing('city'));
        $this->assertTrue($request->missing('name', 'email'));
        $this->assertTrue($request->missing('foo'));
        
        $request = $this->request->withQueryParams(['name' => 'calvin', 'email' => 'foo']);
        $this->assertFalse($request->missing('name', 'email'));
        $this->assertTrue($request->missing('surname', 'password'));
        $this->assertTrue($request->missing(['surname', 'password']));
        
        $request = $this->request->withQueryParams(['foo' => ['bar' => null, 'baz' => '']]);
        $this->assertFalse($request->missing('foo.bar'));
        $this->assertFalse($request->missing('foo.baz'));
        $this->assertTrue($request->missing('foo.bax'));
        $this->assertTrue($request->missing(['foo.bax', 'foo.baz']));
    }
    
    public function testOld()
    {
        $session = new Session(new ArraySessionDriver(10));
        WP::shouldReceive('userId')->andReturn(1)->byDefault();
        $session->start('a');
        $session->flashInput(['foo' => 'bar', 'bar' => 'baz']);
        $session->save();
        
        $request = $this->request->withAttribute('session', $session);
        $this->assertSame(['foo' => 'bar', 'bar' => 'baz'], $request->old());
        $this->assertSame('bar', $request->old('foo'));
        $this->assertSame('default', $request->old('bogus', 'default'));
        
        WP::reset();
        Mockery::close();
    }
    
    public function testOldWithoutSessionSet()
    {
        $this->expectException(RuntimeException::class);
        
        $this->request->old();
    }
    
    public function testValidate()
    {
        $request = $this->request->withQueryParams(
            [
                'foo' => 'bar',
                'baz' => 'biz',
                'team' => [
                    'player' => 'calvin',
                    'coach' => 'marlon',
                    'ceo' => 'john',
                ],
            ]
        
        );
        $request = $request->withValidator(new Validator());
        
        $validated = $request->validate([
            'foo' => v::equals('bar'),
            'baz' => v::equals('biz'),
            'team.player' => v::equals('calvin'),
            'team' => v::contains('marlon'),
        ]);
        
        $expected = [
            'foo' => 'bar',
            'baz' => 'biz',
            'team' => [
                'player' => 'calvin',
                'coach' => 'marlon',
                'ceo' => 'john',
            ],
        ];
        
        $this->assertSame($expected, $validated);
        
        $this->expectException(ValidationException::class);
        
        $request->validate([
            'foo' => v::equals('bar'),
            'baz' => v::equals('biz'),
            'team.player' => v::equals('calvin'),
            'team' => v::contains('jeff'),
        ]);
    }
    
    public function testValidateWithCustomMessages()
    {
        $request = $this->request->withQueryParams(
            [
                'team' => [
                    'player' => 'calvin',
                    'coach' => 'marlon',
                    'ceo' => 'john',
                ],
            ]
        
        );
        $request = $request->withValidator(new Validator());
        
        try {
            $rules = [
                'team.player' => [
                    v::equals('john'),
                    'This is not valid for [attribute]. Must be equal to john',
                ],
            ];
            
            $request->validate($rules, [
                'team.player' => 'The player',
            ]);
            
            $this->fail('Failed validation did not throw exception');
        } catch (ValidationException $e) {
            $errors = $e->errorsAsArray()['team']['player'][0];
            
            $messages = $e->messages();
            
            $this->assertSame(
                'This is not valid for The player. Must be equal to john.',
                $messages->first('team.player')
            );
            
            $this->assertSame('This is not valid for The player. Must be equal to john.', $errors);
        }
    }
    
}