<?php

declare(strict_types=1);

namespace Tests\Validation\unit;

use LogicException;
use Snicco\Support\Arr;
use Respect\Validation\Factory;
use Snicco\Validation\Validator;
use Respect\Validation\Validator as v;
use Tests\Codeception\shared\UnitTest;
use Snicco\Validation\Exceptions\ValidationException;

class ValidatorTest extends UnitTest
{
    
    /** @test */
    public function a_validator_can_be_added()
    {
        $v = new Validator();
        
        $validated = $v->rules([
            
            'count' => v::min(1)->max(3),
        ])
                       ->validate([
                           'count' => 3,
                       ]);
        
        $this->assertSame(['count' => 3], $validated);
        
        $this->expectException(ValidationException::class);
        
        $v
            ->rules([
                'count' => v::min(1)->max(3),
            ])
            ->validate([
                'missing_count' => 3,
            ]);
    }
    
    /** @test */
    public function a_validator_can_be_optional()
    {
        $v = new Validator();
        
        $validated = $v->rules([
            
            'count' => [v::min(1)->max(3), 'optional'],
            'email' => v::email(),
        
        ])
                       ->validate([
                           'missing_count' => 3,
                           'email' => 'c@web.de',
                       ]);
        
        $this->assertSame(['email' => 'c@web.de'], $validated);
        
        $validated = $v->rules([
            
            'count' => [v::min(1)->max(3), 'optional'],
            'email' => v::email(),
        
        ])
                       ->validate([
                           'email' => 'c@web.de',
                       ]);
        
        $this->assertSame(['email' => 'c@web.de'], $validated);
        
        $validated = $v->rules([
            
            'count' => [v::min(1)->max(3), 'optional'],
            'email' => v::email(),
        
        ])
                       ->validate([
                           'count' => 3,
                           'email' => 'c@web.de',
                       ]);
        
        $this->assertSame(['count' => 3, 'email' => 'c@web.de'], $validated);
        
        // present but not valid
        $this->expectException(ValidationException::class);
        $v->rules([
            
            'count' => [v::min(1)->max(3), 'optional'],
            'email' => v::email(),
        
        ])
          ->validate([
              'count' => 4,
              'email' => 'c@web.de',
          ]);
    }
    
    /** @test */
    public function a_validator_can_be_optional_in_combination_with_an_inline_message()
    {
        $v = new Validator();
        
        $validated = $v->rules([
            
            'count' => [v::min(1)->max(3), 'failed', 'optional'],
            'email' => v::email(),
        
        ])
                       ->validate([
                           'missing_count' => 3,
                           'email' => 'c@web.de',
                       ]);
        
        $this->assertSame(['email' => 'c@web.de'], $validated);
        
        $validated = $v->rules([
            
            'count' => [v::min(1)->max(3), 'failed', 'optional'],
            'email' => v::email(),
        
        ])
                       ->validate([
                           'email' => 'c@web.de',
                       ]);
        
        $this->assertSame(['email' => 'c@web.de'], $validated);
        
        $validated = $v->rules([
            
            'count' => [v::min(1)->max(3), 'failed', 'optional'],
            'email' => v::email(),
        
        ])
                       ->validate([
                           'count' => 3,
                           'email' => 'c@web.de',
                       ]);
        
        $this->assertSame(['count' => 3, 'email' => 'c@web.de'], $validated);
        
        // present but not valid
        $this->expectException(ValidationException::class);
        $v->rules([
            
            'count' => [v::min(1)->max(3), 'failed', 'optional'],
            'email' => v::email(),
        
        ])
          ->validate([
              'count' => 4,
              'email' => 'c@web.de',
          ]);
    }
    
    /** @test */
    public function nested_array_values_can_be_validated_with_dot_notation()
    {
        $v = new Validator([
            
            'user1' => [
                'email' => 'c@web.de',
            ],
        
        ]);
        
        $validated = $v->rules([
            'user1.email' => v::email(),
        ])
                       ->validate();
        
        $this->assertSame(['user1' => ['email' => 'c@web.de']], $validated);
    }
    
    /** @test */
    public function nested_array_values_can_be_optional()
    {
        $v = new Validator();
        
        $validated = $v->rules([
            'user1.email' => v::email(),
            'user1.age' => [v::min(18), 'optional'],
        ])
                       ->validate([
            
                           'user1' => [
                               'email' => 'c@web.de',
                           ],
        
                       ]);
        
        $this->assertSame(['user1' => ['email' => 'c@web.de']], $validated);
        
        $this->expectException(ValidationException::class);
        
        // Value present but not satisfied by rule.
        $validated = $v->rules([
            'user1.email' => v::email(),
            'user1.age' => [v::min(18), 'optional'],
        ])
                       ->validate([
            
                           'user1' => [
                               'email' => 'c@web.de',
                               'age' => 17,
                           ],
        
                       ]);
    }
    
    /** @test */
    public function composite_rules_can_be_used()
    {
        $releaseDates = [
            'validation' => '2010-01-01',
            'template' => '2011-01-01',
            'relational' => '2011-02-05',
        ];
        
        $v = new Validator(['release_dates' => $releaseDates]);
        
        $validated = $v->rules([
            'release_dates' => v::each(v::dateTime()),
        ])->validate();
        
        $this->assertSame($validated, ['release_dates' => $releaseDates]);
        
        $this->expectException(ValidationException::class);
        
        $releaseDates = [
            'validation' => '2010-01-01',
            'template' => '2011-01-01',
            'relational' => '2011-02-05',
            'foo' => 'bar',
        ];
        
        $v = new Validator(['release_dates' => $releaseDates]);
        
        $v->rules([
            'release_dates' => v::each(v::dateTime()),
        ])->validate();
    }
    
    /** @test */
    public function the_input_can_be_provided_in_the_constructor()
    {
        $v = new Validator([
            'count' => 3,
        ]);
        
        $validated = $v->rules([
            'count' => v::min(1)->max(3),
        ])->validate();
        
        $this->assertSame(['count' => 3], $validated);
    }
    
    /** @test */
    public function not_providing_any_input_throws_an_exception()
    {
        $v = new Validator();
        
        $this->expectException(LogicException::class);
        
        $v->rules([
            'count' => v::min(1)->max(3),
        ])->validate();
    }
    
    /** @test */
    public function missing_input_for_one_validation_rule_throws_an_exception()
    {
        $v = new Validator();
        
        $input = [
            'count' => 3,
        ];
        
        $this->expectException(ValidationException::class);
        
        $v->rules([
            'count' => v::min(1)->max(3),
            'missing' => v::min(1)->max(3),
        ])->validate($input);
    }
    
    /** @test */
    public function only_validated_data_is_returned()
    {
        $v = new Validator();
        
        $input = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'biz',
            'boo' => 'bam',
        ];
        $validated = $v->rules([
            'foo' => v::equals('bar'),
            'bar' => v::equals('baz'),
        ])->validate($input);
        
        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
        ], $validated);
        
        $input = [
            'foo' => [
                'bar' => ['baz' => 'biz'],
                'bam' => 'boom',
            ],
            'biz' => [
                'baz',
            ],
        ];
        
        $validated = $v->rules([
            
            'foo.bar' => v::arrayType()->contains('biz'),
        
        ])->validate($input);
        
        $this->assertSame([
            'foo' => [
                'bar' => [
                    'baz' => 'biz',
                ],
            ],
        ], $validated);
    }
    
    /** @test */
    public function a_collection_of_data_can_be_validated()
    {
        $_POST = [
            'posts' => [
                [
                    'slug' => 'slug-one',
                    'author' => 'calvin',
                
                ],
                [
                    
                    'slug' => 'slug-two',
                    'author' => 'marlon',
                
                ],
                [
                    
                    'slug' => 'slug-three',
                    'author' => 'john',
                
                ],
            ],
        ];
        
        $v = new Validator($_POST);
        
        $post_validator = v::each(
            v::key('slug', v::length(5))
             ->key('author', v::alpha())
        );
        
        $validated = $v->rules([
            'posts' => $post_validator,
        ])->validate();
        
        $this->assertSame($_POST, $validated);
    }
    
    /** @test */
    public function keys_with_a_leading_star_indicate_to_pass_the_entire_input_to_the_rule()
    {
        Factory::setDefaultInstance(
            (new Factory())
                ->withRuleNamespace('Snicco\Validation\Rules')
                ->withExceptionNamespace('Snicco\Validation\Exceptions')
        );
        
        $_POST = [
            'password' => '123',
            'password_confirmation' => '123',
        ];
        
        $v = new Validator();
        
        $validated = $v->rules([
            
            'password' => v::length(3),
            '*password_confirmation' => v::sameAs('password'),
        
        ])->validate($_POST);
        
        $this->assertSame($_POST, $validated);
    }
    
    /** @test */
    public function a_failing_validation_rule_throws_an_exception()
    {
        $v = new Validator();
        
        $input = [
            'count' => 4,
            'age' => 18,
        ];
        
        try {
            $v->rules([
                'count' => v::min(1)->max(3),
                'age' => v::min(18),
            ])->validate($input);
            
            $this->fail('Failed check did not throw exception.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('count', $e->errorsAsArray());
            $this->assertArrayNotHasKey('age', $e->errorsAsArray());
        }
    }
    
    /** @test */
    public function custom_error_messages_can_be_added_inline()
    {
        $v = new Validator();
        
        $input = [
            'count' => 4,
            'age' => 18,
        ];
        
        try {
            $v->rules([
                
                'count' => [v::min(1)->max(3), 'Your input for count is wrong.'],
                'age' => v::min(18),
            
            ])->validate($input);
            
            $this->fail('Failed check did not throw exception.');
        } catch (ValidationException $e) {
            $errors = $e->errorsAsArray()['count'];
            
            $this->assertContains('Your input for count is wrong.', $errors);
            $this->assertSame('Your input for count is wrong.', $e->messages()->first('count'));
        }
    }
    
    /** @test */
    public function custom_error_messages_can_be_added_with_placeholders()
    {
        $v = new Validator();
        
        $input = [
            'count' => 4,
        ];
        
        try {
            $v->rules([
                'count' => [
                    v::min(1)->max(3),
                    'This is not valid for [attribute].',
                ],
            ])->validate($input);
            
            $this->fail('Failed check did not throw exception.');
        } catch (ValidationException $e) {
            $errors = $e->errorsAsArray()['count'];
            
            $this->assertContains('This is not valid for count.', $errors);
            $this->assertSame('This is not valid for count.', $e->messages()->first('count'));
        }
    }
    
    /** @test */
    public function messages_can_be_passed_with_a_function()
    {
        $v = new Validator([
            'count' => 4,
        ]);
        
        $v->rules([
            
            'count' => v::min(1)->max(3),
        
        ])->messages([
            'count' => 'This is not valid for count.',
        ]);
        
        try {
            $v->validate();
            
            $this->fail('Failed check did not throw exception.');
        } catch (ValidationException $e) {
            $errors = $e->errorsAsArray()['count'];
            
            $this->assertContains('This is not valid for count.', $errors);
            $this->assertSame('This is not valid for count.', $e->messages()->first('count'));
        }
    }
    
    /** @test */
    public function attributes_can_be_customized_for_validation_messages()
    {
        $v = new Validator([
            'email' => 'c.de',
        ]);
        
        $v->rules([
            
            'email' => [v::email(), 'This is not a valid [attribute]'],
        
        ])
          ->attributes([
              'email' => 'email address',
          ]);
        
        try {
            $v->validate();
            
            $this->fail('Failed check did not throw exception.');
        } catch (ValidationException $e) {
            $errors = $e->errorsAsArray()['email'];
            
            $this->assertSame('This is not a valid email address.', $errors[0]);
            $this->assertSame('This is not a valid email address.', $e->messages()->first('email'));
        }
    }
    
    /** @test */
    public function the_input_value_is_replaced_by_default_with_the_attribute_name_if_not_specified_otherwise()
    {
        $v = new Validator();
        
        $input = [
            'user_name' => 'reallymess#edupscreenname',
        ];
        
        try {
            $v->rules([
                
                'user_name' => v::alnum(),
            
            ])
              ->validate($input);
            
            $this->fail('Failed check did not throw exception.');
        } catch (ValidationException $e) {
            $error = $e->errorsAsArray()['user_name'];
            
            $this->assertSame(
                'user_name must contain only letters (a-z) and digits (0-9).',
                $error[0]
            );
            $this->assertSame(
                'user_name must contain only letters (a-z) and digits (0-9).',
                $e->messages()->first('user_name')
            );
        }
    }
    
    /** @test */
    public function composite_error_messages_get_added_correctly()
    {
        $v = new Validator();
        
        $input = [
            'user_name' => 'reallymess#edupscreenname',
        ];
        
        try {
            $v->rules([
                
                'user_name' => v::alnum()->noWhitespace()->length(1, 15),
            
            ])
              ->validate($input);
            
            $this->fail('Failed check did not throw exception.');
        } catch (ValidationException $e) {
            $error = $e->errorsAsArray()['user_name'];
            
            $this->assertSame(
                'user_name must contain only letters (a-z) and digits (0-9).',
                $error[0]
            );
            $this->assertSame('user_name must have a length between 1 and 15.', $error[1]);
            
            $this->assertContains(
                'user_name must contain only letters (a-z) and digits (0-9).',
                $e->messages()->get('user_name')
            );
            $this->assertContains(
                'user_name must have a length between 1 and 15.',
                $e->messages()->get('user_name')
            );
        }
    }
    
    /** @test */
    public function nested_array_values_can_be_validated()
    {
        $submission1 = [
            'post' => [
                'title' => 'My Post',
                'author' => 'c@web.de',
            ],
        ];
        
        $v = new Validator($submission1);
        
        try {
            $v->rules([
                
                'post.title' => [
                    v::noWhitespace(),
                    '[attribute] can not have whitespace',
                ],
                'post.author' => v::email(),
            
            ])->attributes([
                'post.title' => 'The post title',
            ])->validate();
            
            $this->fail('Failed check did not throw exception.');
        } catch (ValidationException $e) {
            $error = Arr::get($e->errorsAsArray(), 'post.title');
            
            $this->assertSame('The post title can not have whitespace.', $error[0]);
            $this->assertSame(
                'The post title can not have whitespace.',
                $e->messages()->first('post.title')
            );
        }
    }
    
    /** @test */
    public function custom_error_bags_can_be_used()
    {
        $submission1 = [
            'post' => [
                'title' => 'My Post',
                'author' => 'c@web.de',
            ],
        ];
        
        $v = new Validator($submission1);
        
        try {
            $v->rules([
                
                'post.title' => [
                    v::noWhitespace(),
                    '[attribute] can not have whitespace',
                ],
                'post.author' => v::email(),
            
            ])->attributes([
                'post.title' => 'The post title',
            ])->validateWithBag('custom');
            
            $this->fail('Failed check did not throw exception.');
        } catch (ValidationException $e) {
            $error = Arr::get($e->errorsAsArray(), 'post.title');
            
            $this->assertSame('The post title can not have whitespace.', $error[0]);
            $this->assertSame(
                'The post title can not have whitespace.',
                $e->messages()->first('post.title')
            );
            $this->assertSame('custom', $e->namedBag());
        }
    }
    
    /** @test */
    public function global_validation_messages_can_be_added_per_failed_rule()
    {
        $submission1 = [
            'author1' => 'c@web.de',
            'author2' => 'john.de',
        ];
        
        $v = new Validator($submission1);
        
        $v->globalMessages([
            'email' => [
                'Leider keine gültige [attribute]',
                'Darf keine gültige [attribute] sein',
                'email addresse',
            ],
        ]);
        
        try {
            $v->rules([
                
                'author1' => v::not(v::email()),
                'author2' => v::email(),
            
            ])->validate();
            
            $this->fail('Failed check did not throw exception.');
        } catch (ValidationException $e) {
            $error = $e->errorsAsArray();
            
            $this->assertSame(
                'Leider keine gültige email addresse.',
                $e->messages()->first('author2')
            );
            $this->assertSame(
                'Darf keine gültige email addresse sein.',
                $e->messages()->first('author1')
            );
        }
    }
    
    /** @test */
    public function global_negated_validation_messages_can_be_added_per_failed_rule()
    {
        $submission1 = [
            'author1' => 'calvin@web.de',
        ];
        
        $v = new Validator($submission1);
        
        $v->globalMessages([
            'email' => [
                '[input] ist keine gültige [attribute]',
                'Wir brauchen deine [attribute]',
                'email addresse',
            ],
        ]);
        
        try {
            $v->rules([
                
                'author1' => v::not(v::email()),
            
            ])->validate();
            
            $this->fail('Failed check did not throw exception.');
        } catch (ValidationException $e) {
            $error = $e->errorsAsArray();
            
            $this->assertSame('Wir brauchen deine email addresse.', $error['author1'][0]);
            $this->assertSame(
                'Wir brauchen deine email addresse.',
                $e->messages()->first('author1')
            );
        }
    }
    
}