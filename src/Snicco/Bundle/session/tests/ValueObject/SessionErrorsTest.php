<?php

declare(strict_types=1);

namespace Snicco\Bundle\Session\Tests\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Bundle\Session\ValueObject\SessionErrors;

final class SessionErrorsTest extends TestCase
{
    /**
     * @test
     */
    public function test_exception_invalid_list_of_errors(): void
    {
        try {
            new SessionErrors([
                'foo',
                'bar',
            ]);
            $this->fail('No exception thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('$errors must be an array keyed by string namespaces.', $e->getMessage());
        }

        try {
            new SessionErrors(
                [
                    'namespace1' => [
                        'foo',
                        'bar',
                    ],
                ]
            );
            $this->fail('No exception thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString(
                'Each error namespace must be an array with string keys.',
                $e->getMessage()
            );
        }

        try {
            new SessionErrors(
                [
                    'namespace1' => [
                        'key1' => [
                            'foo',
                            1,
                        ],
                    ],
                ]
            );
            $this->fail('No exception thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString(
                'All error messages must be strings.',
                $e->getMessage()
            );
        }
    }

    /**
     * @test
     */
    public function test_hasKey(): void
    {
        $errors = new SessionErrors([
            'default' => [
                'key1' => [
                    'foo',
                    'bar',
                ],
                'key2' => [

                ],
            ],
            'form2' => [
                'key3' => [
                    'foo',
                    'bar',
                ],
                'key4' => [
                    'baz',
                    'biz',
                ],
            ],
        ]);
        $this->assertTrue($errors->hasKey('key1'));
        $this->assertFalse($errors->hasKey('key2'));

        $this->assertFalse($errors->hasKey('key3'));
        $this->assertFalse($errors->hasKey('key4'));

        $this->assertTrue($errors->hasKey('key3', 'form2'));
        $this->assertTrue($errors->hasKey('key4', 'form2'));
    }

    /**
     * @test
     */
    public function test_get(): void
    {
        $errors = new SessionErrors([
            'default' => [
                'key1' => [
                    'foo',
                    'bar',
                ],
                'key2' => [

                ],
            ],
            'form2' => [
                'key3' => [
                    'foo',
                    'bar',
                ],
                'key4' => [
                    'baz',
                    'biz',
                ],
            ],
        ]);

        $this->assertSame(['foo', 'bar'], $errors->get('key1'));
        $this->assertSame([], $errors->get('key2'));
        $this->assertSame([], $errors->get('key3'));
        $this->assertSame([], $errors->get('key4'));

        $this->assertSame([], $errors->get('key1', 'form2'));
        $this->assertSame([], $errors->get('key2', 'form2'));
        $this->assertSame(['foo', 'bar'], $errors->get('key3', 'form2'));
        $this->assertSame(['baz', 'biz'], $errors->get('key4', 'form2'));
    }

    /**
     * @test
     */
    public function test_all(): void
    {
        $errors = new SessionErrors([
            'default' => [
                'key1' => [
                    'foo',
                    'bar',
                ],
                'key2' => [
                    'baz',
                ],
            ],
            'form2' => [
                'key3' => [
                    'foo',
                    'bar',
                ],
                'key4' => [
                    'baz',
                    'biz',
                ],
            ],
        ]);

        $this->assertSame(['foo', 'bar', 'baz'], $errors->all());
        $this->assertSame(['foo', 'bar', 'baz', 'biz'], $errors->all('form2'));
    }

    /**
     * @test
     */
    public function test_count(): void
    {
        $errors = new SessionErrors([
            'default' => [
                'key1' => [
                    'foo',
                    'bar',
                ],
                'key2' => [
                ],
            ],
            'form2' => [
                'key3' => [
                    'foo',
                    'bar',
                ],
                'key4' => [
                    'baz',
                    'biz',
                ],
            ],
        ]);

        $this->assertSame(2, $errors->count());
        $this->assertSame(4, $errors->count('form2'));
    }
}
