<?php


    declare(strict_types = 1);


    namespace Tests\unit\Validation;

    use PHPUnit\Framework\TestCase;
    use Respect\Validation\Validator as v;
    use WPEmerge\Support\Arr;
    use WPEmerge\Validation\Exceptions\ValidationException;
    use WPEmerge\Validation\Validator;

    class ValidatorTest extends TestCase
    {

        /** @test */
        public function a_validator_can_be_added () {

            $v = new Validator();

            $input = [
                'count' => 3
            ];

            $validated = $v->rules([
                'count' => v::min(1)->max(3)
            ])->validate($input);

            $this->assertSame( ['count' => 3] , $validated );


        }

        /** @test */
        public function the_input_can_be_provided_in_the_constructor () {

            $v = new Validator([
                'count' => 3
            ]);


            $validated = $v->rules([
                'count' => v::min(1)->max(3)
            ])->validate();

            $this->assertSame( ['count' => 3] , $validated );

        }

        /** @test */
        public function not_providing_any_input_throws_an_exception () {

            $v = new Validator();

            $this->expectException(\LogicException::class);

            $v->rules([
                'count' => v::min(1)->max(3)
            ])->validate();

        }

        /** @test */
        public function missing_input_for_one_validation_rule_throws_an_exception () {

            $v = new Validator();

            $input = [
                'count' => 3
            ];

            $this->expectException(ValidationException::class);

            $v->rules( [
                'count' => v::min(1)->max(3),
                'missing' => v::min(1)->max(3)
            ])->validate($input);


        }

        /** @test */
        public function a_failing_validation_rule_throws_an_exception () {

            $v = new Validator();

            $input = [
                'count' => 4,
                'age' => 18
            ];

            try {

                $v->rules([
                    'count' => v::min(1)->max(3),
                    'age' => v::min(18),
                ])->validate($input);

                $this->fail('Failed check did not throw exception.');

            } catch (ValidationException $e) {

                $this->assertArrayHasKey('count', $e->getErrors());
                $this->assertArrayNotHasKey('age', $e->getErrors());

            }



        }

        /** @test */
        public function custom_error_messages_can_be_added () {

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

                $errors = $e->getErrors()['count'];

                $this->assertContains('Your input for count is wrong.', $errors['messages'] );
                $this->assertSame(4, $errors['input'] );

            }

        }

        /** @test */
        public function custom_error_messages_can_be_added_with_placeholders () {

            $v = new Validator();

            $input = [
                'count' => 4,
            ];

            try {

                $v->rules([
                    'count' => [v::min(1)->max(3), '{{input}} is not valid for {{attribute}}.'],
                ])->validate($input);

                $this->fail('Failed check did not throw exception.');

            } catch (ValidationException $e) {

                $errors = $e->getErrors()['count'];

                $this->assertContains('4 is not valid for count.', $errors['messages'] );
                $this->assertSame(4, $errors['input'] );

            }

        }

        /** @test */
        public function messages_can_be_passed_with_a_function () {

            $v = new Validator([
                'count' => 4,
            ]);

            $v->rules([

                'count' => v::min(1)->max(3),

            ])->messages([
                'count' => '{{input}} is not valid for count.'
            ]);

            try {

                $v->validate();

                $this->fail('Failed check did not throw exception.');

            } catch (ValidationException $e) {

                $errors = $e->getErrors()['count'];

                $this->assertContains('4 is not valid for count.', $errors['messages'] );
                $this->assertSame(4, $errors['input'] );

            }


        }

        /** @test */
        public function attributes_can_be_customized_for_validation_messages () {

            $v = new Validator([
                'email' => 'c.de',
            ]);

            $v->rules([

                'email' => [v::email(), '{{input}} is not a valid {{attribute}}']

            ])
              ->attributes([
                'email' => 'email address'
                ]);

            try {

                $v->validate();

                $this->fail('Failed check did not throw exception.');

            } catch (ValidationException $e) {

                $errors = $e->getErrors()['email'];

                $this->assertSame('c.de is not a valid email address.', $errors['messages'][0]);
                $this->assertSame('c.de', $errors['input'] );

            }


        }

        // /** @test */
        public function composite_error_messages_get_added_correctly () {

            $v = new Validator();

            $input = [
                'user_name' => 'reallymess#edupscreenname',
            ];

            try {

                $v->rules([

                    'user_name' => v::alnum()->noWhitespace()->length(1, 15),

                ])->validate($input);

                $this->fail('Failed check did not throw exception.');

            } catch (ValidationException $e) {

                $error = $e->getErrors()['user_name'];

                $this->assertSame('reallymess#edupscreenname', $error['input'] );
                $this->assertSame('user_name must contain only letters (a-z) and digits (0-9).', $error['messages'][0] );
                $this->assertSame('user_name must have a length between 1 and 15.', $error['messages'][1] );


            }

        }

        /** @test */
        public function nested_array_values_can_be_validated () {

            $submission1 = [
                'post' => [
                    'title' => 'My Post',
                    'author' => 'c@web.de'
                ]
            ];

            $v = new Validator($submission1);

            try {

                $v->rules([

                    'post.title' => [v::alpha(), '{{attribute}} can not have whitespace'],
                    'post.author' => v::email(),

                ])->attributes([
                    'post.title' => 'The post title'
                ])->validate();

                $this->fail('Failed check did not throw exception.');

            } catch (ValidationException $e) {

                $error = Arr::get($e->getErrors(), 'post.title');

                $this->assertSame('My Post', $error['input'] );
                $this->assertSame('The post title can not have whitespace.', $error['messages'][0] );


            }

        }

        /** @test */
        public function global_validation_messages_can_be_added_per_failed_rule () {

            $submission1 = [
                'author1' => 'c@web.de',
                'author2' => 'john.de'
            ];

            $v = new Validator($submission1);

            $v->globalMessages([
                'email' => [
                    '{{input}} ist keine gültige {{attribute}}',
                    '{{input}} darf keine gültige {{attribute}} sein',
                    'email addresse'
                ]
            ]);

            try {

                $v->rules([

                    'author1' => v::email(),
                    'author2' => v::email(),

                ])->validate();

                $this->fail('Failed check did not throw exception.');

            } catch (ValidationException $e) {

                $error = $e->getErrors();

                $this->assertSame('john.de ist keine gültige email addresse.', $error['author2']['messages'][0] );

            }


        }

        /** @test */
        public function global_negated_validation_messages_can_be_added_per_failed_rule () {

            $submission1 = [
                'author1' => 'calvin@web.de',
            ];

            $v = new Validator($submission1);

            $v->globalMessages([
                'email' => [
                    '{{input}} ist keine gültige {{attribute}}',
                    'Wir brauchen deine {{attribute}}',
                    'email addresse'
                ]
            ]);

            try {

                $v->rules([

                    'author1' => v::not(v::email()),

                ])->validate();

                $this->fail('Failed check did not throw exception.');

            } catch (ValidationException $e) {

                $error = $e->getErrors();

                $this->assertSame('Wir brauchen deine email addresse.', $error['author1']['messages'][0] );

            }



        }



    }