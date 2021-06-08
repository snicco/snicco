<?php


    declare(strict_types = 1);


    namespace WPEmerge\Validation;

    use Illuminate\Support\Stringable;
    use Respect\Validation\Exceptions\ValidationException;
    use Respect\Validation\Validator as v;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Str;

    class Validator
    {

        private $errors = [];

        private $custom_messages = [];

        /**
         * @var array
         */
        private $rules;

        /**
         * @var array|null
         */
        private $input;

        /**
         * @var array
         */
        private $replace_attributes;


        public function __construct(?array $input = null)
        {

            $this->input = $input;
        }

        /**
         * @param  array  $rules
         *
         * @return Validator
         */
        public function rules(array $rules) : Validator
        {

            $this->rules = $this->normalizeRules($rules);

            return $this;

        }

        public function validate(?array $input = null) : array
        {

            $pool = $input ?? $this->input;

            if ( ! $pool) {

                throw new \LogicException('No input provided for validation');

            }

            // $validate = Arr::only($pool, array_keys($this->rules));
            $validate = $this->inputToBeValidated($pool);

            foreach ($this->rules as $key => $rule) {

                /** @var v $rule */
                try {

                    $input = $validate[$key] ?? null;

                    $valid = $rule->validate($input);

                    if ( ! $valid) {

                        $rule->assert($input);

                    }

                }
                catch (ValidationException $e) {

                    $this->addToErrors($e, $key, $input);

                }

            }

            if (count($this->errors)) {

                throw new Exceptions\ValidationException($this->errors);

            }

            return $validate;

        }

        private function addToErrors(ValidationException $e, $key, $input)
        {

            $messages = $this->reformatMessages($e->getMessages(), $key, $input);

            Arr::set($this->errors, $key,['input' => $input, 'messages' => $messages]);


        }

        private function reformatMessages(array $messages, $name, $input) : array
        {

            return collect($messages)
                ->map(function ($value) use ($input, $name) {

                    return str_replace($input, $name, $value);

                })
                ->map(function ($message) use ($input, $name) {

                    return $this->replaceWithCustomMessage($message, $input, $name);

                })
                ->map(function ($message) {

                    if ( ! Str::endsWith($message, '.')) {

                        $message .= '.';

                    }

                    return $message;

                })
                ->values()->all();

        }

        private function normalizeRules(array $rules) : array
        {

            return collect($rules)
                ->map(function ($rule) {

                    return Arr::wrap($rule);

                })
                ->flatMap(function (array $rule, $key) {

                    /** @var v $v */
                    $v = $rule[0];

                    if (isset($rule[1])) {

                        $this->custom_messages[$key] = $rule[1];
                    }

                    return [$key => $v];

                })
                ->all();

        }

        private function replaceWithCustomMessage($message, $input, $name) : string
        {

            $message = $this->custom_messages[$name] ?? $message;

            $message = (string) Str::of($message)
                                ->replace('"', '')
                               ->replace('{{input}}', $this->readable($input));

            return $this->replaceCustomAttributeNames($message, $name);

        }

        private function replaceCustomAttributeNames(string $message, string $name) :string
        {

            $replacement = $this->replace_attributes[$name] ?? $name;

            if(Str::contains($message , '{{attribute}}') ) {

                return str_replace('{{attribute}}', $replacement, $message);
            }

            return str_replace($name, $replacement, $message);

        }

        private function readable($input) : string
        {

            if ($input === null) {
                return 'null';
            }

            if (is_scalar($input)) {
                return strval($input);
            }

        }

        public function messages(array $messages) : Validator
        {

            $this->custom_messages = $messages;

            return $this;

        }

        public function attributes(array $attributes) : Validator
        {

            $this->replace_attributes = $attributes;

            return $this;
        }

        private function inputToBeValidated(array $pool)
        {

            $keys = array_keys($this->rules);

            $validate = [];

            foreach ($keys as $key) {

                $validate[$key] = Arr::get($pool, $key);

            }

            return $validate;

        }


    }