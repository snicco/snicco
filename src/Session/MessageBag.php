<?php

declare(strict_types=1);

namespace Snicco\Session;

use Countable;
use JsonSerializable;
use Snicco\Support\Arr;
use Snicco\Support\Str;

final class MessageBag implements JsonSerializable, Countable
{
    
    /**
     * All of the registered messages.
     *
     * @var array
     */
    protected $messages = [];
    
    /**
     * Default format for message output.
     *
     * @var string
     */
    protected $format = ':message';
    
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
    
    /**
     * Get the keys present in the message bag.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->messages);
    }
    
    /**
     * Add a message to the message bag.
     *
     * @param  string  $key
     * @param  string  $message
     *
     * @return $this
     */
    public function add($key, $message)
    {
        if ($this->isUnique($key, $message)) {
            $this->messages[$key][] = $message;
        }
        
        return $this;
    }
    
    /**
     * Add a message to the message bag if the given conditional is "true".
     *
     * @param  bool  $boolean
     * @param  string  $key
     * @param  string  $message
     *
     * @return $this
     */
    public function addIf($boolean, $key, $message)
    {
        return $boolean ? $this->add($key, $message) : $this;
    }
    
    /**
     * Merge a new array of messages into the message bag.
     *
     * @param  MessageBag|array  $messages
     *
     * @return $this
     */
    public function merge($messages)
    {
        if ($messages instanceof MessageBag) {
            $messages = $messages->getMessages();
        }
        
        $this->messages = Arr::mergeRecursive($this->messages, $messages);
        
        return $this;
    }
    
    /**
     * Determine if messages exist for all of the given keys.
     *
     * @param  array|string|null  $key
     *
     * @return bool
     */
    public function has($key)
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
     *
     * @return bool
     */
    public function hasAny($keys = [])
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
     *
     * @param  string|null  $key
     * @param  string|null  $format
     *
     * @return string
     */
    public function first($key = null, $format = null)
    {
        $messages = is_null($key) ? $this->all($format) : $this->get($key, $format);
        
        $firstMessage = Arr::first($messages, null, '');
        
        return is_array($firstMessage) ? Arr::first($firstMessage) : $firstMessage;
    }
    
    /**
     * Get all of the messages from the message bag for a given key.
     *
     * @param  string  $key
     * @param  string|null  $format
     *
     * @return array
     */
    public function get($key, $format = null)
    {
        // If the message exists in the message bag, we will transform it and return
        // the message. Otherwise, we will check if the key is implicit & collect
        // all the messages that match the given key and output it as an array.
        if (array_key_exists($key, $this->messages)) {
            return $this->transform(
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
     * Get all of the messages for every key in the message bag.
     *
     * @param  string|null  $format
     *
     * @return array
     */
    public function all($format = null)
    {
        $format = $this->checkFormat($format);
        
        $all = [];
        
        foreach ($this->messages as $key => $messages) {
            $all = array_merge($all, $this->transform($messages, $format, $key));
        }
        
        return $all;
    }
    
    /**
     * Get all of the unique messages for every key in the message bag.
     *
     * @param  string|null  $format
     *
     * @return array
     */
    public function unique($format = null)
    {
        return array_unique($this->all($format));
    }
    
    /**
     * Get the raw messages in the message bag.
     *
     * @return array
     */
    public function messages()
    {
        return $this->messages;
    }
    
    /**
     * Get the raw messages in the message bag.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages();
    }
    
    /**
     * Determine if the message bag has any messages.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return ! $this->any();
    }
    
    /**
     * Determine if the message bag has any messages.
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return $this->any();
    }
    
    /**
     * Determine if the message bag has any messages.
     *
     * @return bool
     */
    public function any()
    {
        return $this->count() > 0;
    }
    
    /**
     * Get the number of messages in the message bag.
     *
     * @return int
     */
    public function count()
    {
        return count($this->messages, COUNT_RECURSIVE) - count($this->messages);
    }
    
    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getMessages();
    }
    
    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
    
    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }
    
    /**
     * Convert the message bag to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
    
    /**
     * Determine if a key and message combination already exists.
     *
     * @param  string  $key
     * @param  string  $message
     *
     * @return bool
     */
    protected function isUnique($key, $message)
    {
        $messages = $this->messages;
        
        return ! isset($messages[$key]) || ! in_array($message, $messages[$key]);
    }
    
    /**
     * Get the messages for a wildcard key.
     *
     * @param  string  $key
     * @param  string|null  $format
     *
     * @return array
     */
    protected function getMessagesForWildcardKey($key, $format)
    {
        $messages = array_filter($this->messages, function ($messages, $message_key) use ($key) {
            return Str::is($key, $message_key);
        });
        
        return array_map(function ($messages, $message_key) use ($format) {
            return $this->transform(
                $messages,
                $this->checkFormat($format),
                $message_key
            );
        }, $messages);
    }
    
    /**
     * Format an array of messages.
     *
     * @param  array  $messages
     * @param  string  $format
     * @param  string  $messageKey
     *
     * @return array
     */
    protected function transform($messages, $format, $messageKey)
    {
        return array_map(function ($message) use ($format, $messageKey) {
            // We will simply spin through the given messages and transform each one
            // replacing the :message place holder with the real message allowing
            // the messages to be easily formatted to each developer's desires.
            return str_replace([':message', ':key'], [$message, $messageKey], $format);
        }, $messages);
    }
    
    /**
     * Get the appropriate format based on the given format.
     *
     * @param  string  $format
     *
     * @return string
     */
    protected function checkFormat($format)
    {
        return $format ? : $this->format;
    }
    
}