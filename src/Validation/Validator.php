<?php


    declare(strict_types = 1);


    namespace WPEmerge\Validation;

    use Respect\Validation\Exceptions\ValidationException;
    use Respect\Validation\Rules\Not;
    use Respect\Validation\Validator as v;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Str;

    use function Respect\Stringifier\stringify;

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
        private $replace_attributes = [];

        /**
         * @var array
         */
        private $global_message_replacements = [];


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

            if ( ! $pool ) {

                throw new \LogicException('No input provided for validation');

            }

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

                    $this->updateGlobalTemplates($e, $key, $input);

                    $this->addToErrors($e, $key, $input);

                }

            }

            if (count($this->errors)) {

                throw new Exceptions\ValidationException($this->errors);

            }

            return $validate;

        }

        public function messages(array $messages) : Validator
        {

            $this->custom_messages = array_merge($this->custom_messages, $messages);

            return $this;

        }

        public function attributes(array $attributes) : Validator
        {

            $this->replace_attributes = array_merge($this->replace_attributes, $attributes);

            return $this;
        }

        public function globalMessages(array $messages)
        {

            $this->global_message_replacements = $messages;
        }

        private function addToErrors(ValidationException $e, $key, $input)
        {


            $messages = $this->reformatMessages($e, $key, $input);

            Arr::set($this->errors, $key, ['input' => $input, 'messages' => $messages]);


        }

        private function reformatMessages(ValidationException $e, $name, $input) : array
        {

            $exceptions = $e->getChildren();

            $messages = collect($exceptions)
                ->map(function (ValidationException $e) use ($input, $name) {

                    $message = $this->replaceInputWithName($input, $e->getMessage(), $name);

                    return ['id' => $e->getId(), 'message' => $message];

                })
                ->map(function (array $exception) use ($input) {
                    return [
                        'id' => $exception['id'],
                        'message' => $this->replaceInputWithPlaceholder($input, $exception['message']),
                    ];
                })
                ->map(function (array $exception) use ($input, $name) {

                    return $this->replaceWithCustomMessage($exception['message'],$input, $name, $exception['id']);

                })
                ->map(function ($message) {

                    if ( ! Str::endsWith($message, '.')) {

                        $message .= '.';

                    }

                    return $message;

                })
                ->values()
                ->all();

            return $messages;



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

        private function replaceInputWithName($search_for, string $in, $replace_with ) {

            return str_replace($search_for, $replace_with, $in);

        }

        private function replaceWithCustomMessage($message, $input, $name, $rule_id) : string
        {

            $message = $this->custom_messages[$name] ?? $message;

            $message = (string) Str::of($message)
                                   ->replace('{{input}}', $this->readable($input));

            return $this->replaceCustomAttributeNames($message, $name, $rule_id);

        }

        private function updateGlobalTemplates(ValidationException $e, $name, $input)
        {

            $negated_template = $e->getParam('rules')[0] instanceof Not;

            collect($e->getChildren())->each(function (ValidationException $e) use ($input, $name, $negated_template) {

                if ( ! isset($this->global_message_replacements[$e->getId()])) {
                    return;
                }

                if ($negated_template) {

                    $e->updateMode(ValidationException::MODE_NEGATIVE);
                    $e->updateTemplate($this->global_message_replacements[$e->getId()][1]);

                    return;

                }

                $e->updateTemplate($this->global_message_replacements[$e->getId()][0]);

            });

        }

        private function replaceCustomAttributeNames(string $message, string $name, string $rule_id) : string
        {

            $replacement = $this->global_message_replacements[$rule_id][2] ?? $name;
            $replacement = $this->replace_attributes[$name] ?? $replacement;

            if (Str::contains($message, '{{attribute}}')) {

                return str_replace('{{attribute}}', $replacement, $message);
            }

            return str_replace($name, $replacement, $message);

        }

        private function readable($input) : string
        {

            return trim(stringify($input), '"');

        }

        private function inputToBeValidated(array $pool) : array
        {

            $keys = array_keys($this->rules);

            $validate = [];

            foreach ($keys as $key) {

                $validate[$key] = Arr::get($pool, $key);

            }

            return $validate;

        }

        private function replaceInputWithPlaceholder( $search_for, string $in)
        {

            return str_replace('"'.$search_for.'"', '{{input}}', $in);

        }

    }