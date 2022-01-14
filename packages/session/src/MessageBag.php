<?php

declare(strict_types=1);

/*
 * Class greatly inspired and in most parts copied from Laravel`s MessageBag
 * @see https://github.com/laravel/framework/blob/v8.76.1/src/Illuminate/Support/MessageBag.php
 * License: The MIT License (MIT) https://github.com/illuminate/support/blob/master/LICENSE.md
 * Copyright (c) Taylor Otwell
 */

namespace Snicco\Session;

use Countable;
use JsonSerializable;
use Snicco\StrArr\Arr;
use Snicco\StrArr\Str;

use function count;
use function is_null;
use function is_array;
use function filter_var;
use function array_keys;
use function json_encode;
use function array_unique;
use function func_get_args;

final class MessageBag implements JsonSerializable, Countable
{
    
    /**
     * @var array
     */
    private $messages = [];
    
    /**
     * @var string
     */
    private $message_placeholder = ':message';
    
    /**
     * @var string
     */
    private $key_placeholder = ':key';
    
    /**
     * Create a new message bag instance.
     *
     * @param  array  $messages
     *
     * @return void
     */
    public function __construct(array $messages = [])
    {
        foreach ($messages as $key => $value) {
            $value = (array) $value;
            $this->messages[$key] = array_unique($value);
        }
    }
    
    public function keys() :array
    {
        return array_keys($this->messages);
    }
    
    public function add(string $key, string $message) :void
    {
        if ($this->isUnique($key, $message)) {
            $this->messages[$key][] = $message;
        }
    }
    
    public function addIf($boolean, string $key, string $message) :void
    {
        if (filter_var($boolean, FILTER_VALIDATE_BOOLEAN)) {
            $this->add($key, $message);
        }
    }
    
    /**
     * @param  MessageBag|array  $messages
     */
    public function merge($messages) :void
    {
        if ($messages instanceof MessageBag) {
            $messages = $messages->messages();
        }
        
        $this->messages = Arr::mergeRecursive($this->messages, $messages);
    }
    
    /**
     * Determine if messages exist for all the given keys.
     *
     * @param  array|string|null  $key
     */
    public function has($key) :bool
    {
        if ($this->isEmpty()) {
            return false;
        }
        
        if (is_null($key)) {
            return $this->any();
        }
        
        $keys = is_array($key) ? $key : func_get_args();
        
        foreach ($keys as $key) {
            if ($this->first($key) === '') {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Determine if messages exist for any of the given keys.
     *
     * @param  array|string  $keys
     */
    public function hasAny($keys = []) :bool
    {
        if ($this->isEmpty()) {
            return false;
        }
        
        $keys = is_array($keys) ? $keys : func_get_args();
        
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get the first message from the message bag for a given key.
     */
    public function first(?string $key = null, ?string $format = null) :string
    {
        $messages = is_null($key) ? $this->all($format) : $this->get($key, $format);
        
        $first_message = Arr::first($messages, null, '');
        
        return is_array($first_message) ? Arr::first($first_message) : $first_message;
    }
    
    /**
     * Get all the messages from the message bag for a given key.
     */
    public function get(string $key, ?string $format = null) :array
    {
        if (array_key_exists($key, $this->messages)) {
            return $this->replacePlaceholders(
                $this->messages[$key],
                $this->checkFormat($format),
                $key
            );
        }
        
        if (Str::contains($key, '*')) {
            return $this->getMessagesForWildcardKey($key, $format);
        }
        
        return [];
    }
    
    /**
     * Get all the messages for every key in the message bag.
     */
    public function all(?string $format = null) :array
    {
        $format = $this->checkFormat($format);
        
        $all = [];
        
        foreach ($this->messages as $key => $messages) {
            $all = array_merge(
                $all,
                $this->replacePlaceholders($messages, $format, $key)
            );
        }
        
        return $all;
    }
    
    /**
     * Get all the unique messages for every key in the message bag.
     */
    public function unique(?string $format = null) :array
    {
        return array_unique($this->all($format));
    }
    
    public function messages() :array
    {
        return $this->messages;
    }
    
    public function isEmpty() :bool
    {
        return ! $this->any();
    }
    
    public function isNotEmpty() :bool
    {
        return $this->any();
    }
    
    public function any() :bool
    {
        return $this->count() > 0;
    }
    
    public function count() :int
    {
        return count($this->messages, COUNT_RECURSIVE) - count($this->messages);
    }
    
    public function toArray() :array
    {
        return $this->messages();
    }
    
    public function jsonSerialize()
    {
        return $this->toArray();
    }
    
    public function toJson(int $options = 0) :string
    {
        return json_encode($this->jsonSerialize(), $options);
    }
    
    public function __toString() :string
    {
        return $this->toJson();
    }
    
    private function checkFormat(?string $format = null) :string
    {
        return $format ? : $this->message_placeholder;
    }
    
    private function getMessagesForWildcardKey(string $key, ?string $format) :array
    {
        $messages = array_filter($this->messages, function ($messages, $message_key) use ($key) {
            return Str::is($key, $message_key);
        });
        
        return array_map(function ($messages, $message_key) use ($format) {
            return $this->replacePlaceholders(
                $messages,
                $this->checkFormat($format),
                $message_key
            );
        }, $messages);
    }
    
    private function replacePlaceholders(array $messages, string $format, $message_key) :array
    {
        return array_map(function ($message) use ($format, $message_key) {
            return str_replace(
                [$format, $this->key_placeholder],
                [$message, $message_key],
                $message
            );
        }, $messages);
    }
    
    private function isUnique(string $key, string $message) :bool
    {
        $messages = $this->messages;
        
        return ! isset($messages[$key]) || ! in_array($message, $messages[$key]);
    }
    
}