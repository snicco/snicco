<?php

declare(strict_types=1);

namespace Snicco\Validation;

use LogicException;
use Snicco\Support\Arr;
use Snicco\Support\Str;
use Snicco\Session\MessageBag;
use Respect\Validation\Rules\Not;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException as RespectValidationError;

class Validator
{
    
    private array      $errors_as_array             = [];
    private array      $custom_messages             = [];
    private array      $rules                       = [];
    private ?array     $input;
    private array      $replace_attributes          = [];
    private array      $global_message_replacements = [];
    private MessageBag $message_bag;
    private string     $message_bag_name            = 'default';
    
    public function __construct(?array $input = null)
    {
        $this->input = $input;
        $this->message_bag = new MessageBag();
    }
    
    /**
     * @param  array  $rules
     *
     * @return Validator
     */
    public function rules(array $rules) :Validator
    {
        $this->rules = $this->normalizeRules($rules);
        
        return $this;
    }
    
    public function validateWithBag(string $named_bag, ?array $input = null) :array
    {
        $this->message_bag_name = $named_bag;
        
        return $this->validate($input);
    }
    
    public function validate(?array $input = null) :array
    {
        $input = $input ?? $this->input;
        
        if ( ! is_array($input)) {
            throw new LogicException('No input provided for validation');
        }
        
        $complete_input = $this->inputToBeValidated($input);
        
        foreach ($this->rules as $key => $validator) {
            /** @var v $validator */
            try {
                $input = $this->buildRuleInput($key, $complete_input);
                
                $valid = $validator->validate($input);
                
                if ( ! $valid) {
                    $validator->assert($input);
                }
            } catch (RespectValidationError $e) {
                $this->updateGlobalTemplates($e, $key, $input);
                
                $this->addToErrors($e, $key, $input);
            }
        }
        
        if (count($this->errors_as_array)) {
            $e = new Exceptions\ValidationException($this->errors_as_array);
            $e->setMessageBag($this->message_bag, $this->message_bag_name);
            
            throw $e;
        }
        
        return $complete_input;
    }
    
    public function messages(array $messages) :Validator
    {
        $this->custom_messages = array_merge($this->custom_messages, $messages);
        
        return $this;
    }
    
    public function attributes(array $attributes) :Validator
    {
        $this->replace_attributes = array_merge($this->replace_attributes, $attributes);
        
        return $this;
    }
    
    public function globalMessages(array $messages)
    {
        $this->global_message_replacements = $messages;
    }
    
    private function normalizeRules(array $rules) :array
    {
        $_r = [];
        
        foreach ($rules as $key => $rule) {
            $rule = Arr::wrap($rule);
            
            if ( ! isset($rule[2])) {
                $optional = ($rule[1] ?? 'required') === 'optional';
            }
            else {
                $optional = ($rule[2] ?? 'required') === 'optional';
            }
            
            if (isset($rule[1]) && ! Str::contains($rule[1], ['optional', 'required'])) {
                $this->custom_messages[trim($key, '*')] = $rule[1];
            }
            
            if ($optional) {
                $rule[0] = v::nullable($rule[0]);
            }
            
            $_r[$key] = $rule[0];
        }
        
        return $_r;
    }
    
    private function inputToBeValidated(array $pool) :array
    {
        $keys = array_filter(array_keys($this->rules), function ($key) {
            return ! is_int($key);
        });
        
        $validate = [];
        
        foreach ($keys as $key) {
            $key = trim($key, '*');
            
            $value = Arr::get($pool, $key);
            $value = is_string($value) ? trim($value) : $value;
            
            Arr::set($validate, $key, $value);
        }
        
        return Arr::removeNullRecursive($validate);
    }
    
    private function buildRuleInput($key, array $complete_input)
    {
        if (Str::startsWith($key, '*')) {
            $complete_input['__mapped_key'] = trim($key, '*');
            
            return $complete_input;
        }
        
        return Arr::get($complete_input, $key);
    }
    
    private function updateGlobalTemplates(RespectValidationError $e, $name, $input)
    {
        $negated_template = $e->getParam('rules')[0] instanceof Not;
        
        foreach ($e->getChildren() as $e) {
            if ( ! isset($this->global_message_replacements[$e->getId()])) {
                return;
            }
            
            if ($negated_template) {
                $e->updateMode(RespectValidationError::MODE_NEGATIVE);
                $e->updateTemplate($this->global_message_replacements[$e->getId()][1]);
                
                return;
            }
            
            $e->updateTemplate($this->global_message_replacements[$e->getId()][0]);
        }
    }
    
    private function addToErrors(RespectValidationError $e, $key, $input)
    {
        $messages = $this->reformatMessages($e, $key, $input);
        
        Arr::set($this->errors_as_array, $key, $messages);
        
        foreach ($messages as $message) {
            $this->message_bag->add(ltrim($key, '*'), $message);
        }
    }
    
    private function reformatMessages(RespectValidationError $e, $name, $input) :array
    {
        $exceptions = $e->getChildren();
        
        $messages = $exceptions;
        
        $rule_id = null;
        
        $messages = array_map(function (RespectValidationError $e) use ($name, &$rule_id) {
            $rule_id = $e->getId();
            return $this->replaceWithCustomMessage($e, $name);
        }, $messages);
        
        $messages = array_map(function ($message) use ($input, $name) {
            return $this->replaceRawInputWithAttributeName($input, $name, $message);
        }, $messages);
        
        $messages = array_map(function ($message) use ($input, $name, $rule_id) {
            $message = $this->swapOutPlaceHolders($message, $name, $input, $rule_id);
            return $this->addTrailingPoint($message);
        }, $messages);
        
        return array_values($messages);
    }
    
    private function replaceWithCustomMessage($e, $attribute_name)
    {
        return $this->custom_messages[Str::after($attribute_name, '*')] ?? $e->getMessage();
    }
    
    private function replaceRawInputWithAttributeName($input, $name, $message)
    {
        if ( ! is_scalar($input)) {
            return $message;
        }
        
        return str_replace('"'.$input.'"', $name, $message);
    }
    
    private function swapOutPlaceHolders($message, $name, $input, $rule_id)
    {
        return $this->replaceCustomAttributeNames($message, $name, $rule_id);
    }
    
    private function replaceCustomAttributeNames(string $message, string $name, string $rule_id) :string
    {
        $replacement = $this->global_message_replacements[$rule_id][2] ?? $name;
        $replacement = $this->replace_attributes[$name] ?? $replacement;
        
        if (Str::contains($message, '[attribute]')) {
            return str_replace('[attribute]', $replacement, $message);
        }
        
        return str_replace($name, $replacement, $message);
    }
    
    private function addTrailingPoint($message)
    {
        if ( ! Str::endsWith($message, '.')) {
            $message .= '.';
        }
        
        return str_replace('"', '', $message);
    }
    
}