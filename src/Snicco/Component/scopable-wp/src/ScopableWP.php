<?php

declare(strict_types=1);

namespace Snicco\Component\ScopableWP;

use BadMethodCallException;
use LogicException;
use RuntimeException;
use WP_User;

use function add_action;
use function add_filter;
use function apply_filters_ref_array;
use function array_merge;
use function call_user_func_array;
use function ctype_lower;
use function current_user_can;
use function do_action;
use function function_exists;
use function get_current_user_id;
use function gettype;
use function is_user_logged_in;
use function preg_replace;
use function sprintf;
use function strtolower;
use function trigger_error;
use function ucwords;
use function wp_cache_delete;
use function wp_cache_get;
use function wp_cache_set;
use function wp_get_current_user;

use const E_USER_NOTICE;

/**
 * Extend this class in your code to and add only the methods that your reference in your code.
 * ALWAYS call WordPress code through this class and your plugin will be scopable with ease.
 * CONSIDER ALL METHODS IN THIS CLASS FINAL. The only reason they are not marked as final is that
 * an interface does not make sense for this package and because mocking calls to WordPress would
 * be
 * extremely inconvenient with methods being final. That said, you should never overwrite any
 * methods of this class.
 *
 * @api
 * @note No new (public) methods will be added to this class until a next major version.
 */
class ScopableWP
{

    private const VERSION = '1.0.0';

    /**
     * @var array<string,string>
     */
    private static array $snake_cache = [];

    public function __call(string $name, array $arguments)
    {
        $method = $this->methodNameToSnakeCase($name);
        $prefixed = '\\wp_' . $method;
        $global = '\\' . $method;

        if (function_exists($prefixed)) {
            $proxy_to = $prefixed;
        } elseif (function_exists($global)) {
            $proxy_to = $global;
        } else {
            throw new BadMethodCallException(
                sprintf(
                    'Method [%s] is not defined on class [%s] and neither [%s] nor [%s] are defined in the global namespace.',
                    $name,
                    static::class,
                    $prefixed,
                    $global
                )
            );
        }

        $this->triggerNotice($name, $proxy_to);

        return call_user_func_array($proxy_to, $arguments);
    }

    /**
     * @final
     * @return true
     */
    public function addFilter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return add_filter($hook_name, $callback, $priority, $accepted_args);
    }

    /**
     * @final
     * @return true
     */
    public function addAction(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        return add_action($hook_name, $callback, $priority, $accepted_args);
    }

    /**
     * @final
     *
     * @param mixed ...$args
     */
    public function doAction(string $hook_name, ...$args): void
    {
        do_action($hook_name, ...$args);
    }

    /**
     * @template T
     * @param string $hook_name
     * @param T $value
     * @param mixed ...$args
     * @return T
     *
     * @psalm-suppress MixedAssignment
     */
    public function applyFiltersStrict(string $hook_name, $value, ...$args)
    {
        $res = $this->applyFilters($hook_name, $value, ...$args);
        $expected = gettype($value);
        $actual = gettype($res);
        if ($actual !== $expected) {
            throw new LogicException(
                "Initial value [$value] for filter [$hook_name] is $expected. Returned [$actual]."
            );
        }
        /** @var T $res */
        return $res;
    }

    /**
     * @final
     *
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    public function applyFilters(string $hook_name, $value, ...$args)
    {
        return apply_filters_ref_array($hook_name, array_merge([$value], $args));
    }

    /**
     * @final
     */
    public function isUserLoggedIn(): bool
    {
        return is_user_logged_in();
    }

    /**
     * @final
     */
    public function getCurrentUser(): WP_User
    {
        return wp_get_current_user();
    }

    /** @final */
    public function getCurrentUserId(): int
    {
        return get_current_user_id();
    }

    /**
     * @final
     * @return mixed|false
     */
    public function cacheGet(string $key, string $group = '', bool $force = false, bool &$found = null)
    {
        return wp_cache_get($key, $group, $force, $found);
    }

    /**
     * @final
     * @param mixed $data
     */
    public function cacheSet(string $key, $data, string $group = '', int $expire = 0): bool
    {
        return wp_cache_set($key, $data, $group, $expire);
    }

    /**
     * @final
     */
    public function cacheDelete(string $key, string $group = ''): bool
    {
        return wp_cache_delete($key, $group);
    }

    /**
     * @final
     *
     * @param mixed $args
     */
    public function currentUserCan(string $capability, ...$args): bool
    {
        return current_user_can($capability, ...$args);
    }

    private function methodNameToSnakeCase(string $method_name): string
    {
        $key = $method_name;

        if (isset(self::$snake_cache[$key])) {
            return self::$snake_cache[$key];
        }

        if (!ctype_lower($method_name)) {
            $method_name = preg_replace('/\s+/u', '', ucwords($method_name));

            if (null === $method_name) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException(
                    sprintf("preg_replace returned null in [%s].\nLine: [%s]", self::class, __LINE__)
                );
                // @codeCoverageIgnoreEnd
            }

            $method_name = preg_replace(
                '/(.)(?=[A-Z])/u',
                '$1' . '_',
                $method_name
            );

            if (null === $method_name) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException(
                    sprintf("preg_replace returned null in [%s].\nLine: [%s].", self::class, __LINE__)
                );
                // @codeCoverageIgnoreEnd
            }

            $method_name = strtolower($method_name);
        }

        return static::$snake_cache[$key] = $method_name;
    }

    private function triggerNotice(string $called_instance_method, string $proxies_to): void
    {
        trigger_error(
            sprintf(
                "Tried to call method [%s] on [%s] but its not defined. There might be an autoload conflict.\nUsed version of scopable-wp [%s].\nProxying to global function [%s].",
                $called_instance_method,
                static::class,
                self::VERSION,
                $proxies_to
            ),
            E_USER_NOTICE
        );
    }


}