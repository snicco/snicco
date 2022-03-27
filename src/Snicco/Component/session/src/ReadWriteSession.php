<?php

declare(strict_types=1);

namespace Snicco\Component\Session;

use Closure;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Event\SessionRotated;
use Snicco\Component\Session\Exception\SessionIsLocked;
use Snicco\Component\Session\Serializer\Serializer;
use Snicco\Component\Session\SessionManager\SessionManager;
use Snicco\Component\Session\ValueObject\ReadOnlySession;
use Snicco\Component\Session\ValueObject\SerializedSession;
use Snicco\Component\Session\ValueObject\SessionId;
use Snicco\Component\StrArr\Arr;

use function array_diff;
use function array_merge;
use function array_unique;
use function filter_var;
use function is_array;
use function is_int;
use function is_string;

/**
 * You should depend on {@see MutableSession} or {@see ImmutableSession}
 * depending on your use case.
 *
 * @psalm-internal Snicco
 */
final class ReadWriteSession implements Session
{
    private SessionId $id;

    private int $last_activity;

    private array $attributes;

    private array $original_attributes;

    private bool $locked = false;

    private bool $is_new = false;

    private ?SessionId $invalidated_id = null;

    /**
     * @var list<object>
     */
    private array $stored_events = [];

    /**
     * @interal Sessions MUST only be started from a {@see SessionManager}
     *
     * @param mixed[] $data
     */
    public function __construct(SessionId $id, array $data, int $last_activity)
    {
        $this->id = $id;
        $this->attributes = $data;

        if (! $this->has('_sniccowp.timestamps.created_at')) {
            $this->put('_sniccowp.timestamps.created_at', $last_activity);
            $this->is_new = true;
        }

        if (! $this->has('_sniccowp.timestamps.last_rotated')) {
            $this->put('_sniccowp.timestamps.last_rotated', $last_activity);
        }

        $this->original_attributes = $this->attributes;
        $this->last_activity = $last_activity;
    }

    public static function createEmpty(int $last_activity): ReadWriteSession
    {
        return new self(SessionId::new(), [], $last_activity);
    }

    public function has(string $key): bool
    {
        return null !== Arr::get($this->attributes, $key);
    }

    /**
     * @param string|array<string,mixed> $key
     * @param mixed|null                 $value
     */
    public function put($key, $value = null): void
    {
        $this->checkIfLocked();

        if (is_string($key)) {
            $key = [
                $key => $value,
            ];
        }

        /**
         * @var mixed $array_value
         */
        foreach ($key as $array_key => $array_value) {
            Arr::set($this->attributes, $array_key, $array_value);
        }
    }

    public function all(): array
    {
        return Arr::except($this->attributes, '_sniccowp');
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function get(string $key, $default = null)
    {
        return Arr::get($this->attributes, $key, $default);
    }

    public function createdAt(): int
    {
        $ts = $this->get('_sniccowp.timestamps.created_at');
        if (! is_int($ts)) {
            throw new RuntimeException(
                'The session storage seems corrupted as the value for key [_sniccowp.timestamps.created_at] is not an integer.'
            );
        }

        return $ts;
    }

    public function decrement(string $key, int $amount = 1): void
    {
        $this->increment($key, $amount * -1);
    }

    public function increment(string $key, int $amount = 1, int $start_value = 0): void
    {
        if (! $this->has($key)) {
            $this->put($key, $start_value);
        }

        $current = $this->get($key, 0);
        if (! is_int($current)) {
            throw new LogicException(sprintf('Current value for key [%s] is not an integer.', $key));
        }

        $this->put($key, $current + $amount);
    }

    public function flashInput(array $input): void
    {
        $this->checkIfLocked();
        $this->flash('_old_input', $input);
    }

    public function flash(string $key, $value = true): void
    {
        $this->checkIfLocked();
        $this->put($key, $value);

        $this->push('_flash.new', $key);

        $this->removeFromOldFlashData([$key]);
    }

    /**
     * @psalm-suppress MixedAssignment
     *
     * @param mixed $value
     */
    public function push(string $key, $value): void
    {
        $array = $this->get($key, []);

        if (! is_array($array)) {
            throw new LogicException(sprintf('Value for key [%s] is not an array.', $key));
        }

        $array[] = $value;

        $this->put($key, $array);
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function hasOldInput(string $key = null): bool
    {
        $old = $this->oldInput($key);

        return null === $key
            ? is_array($old) && [] !== $old
            : null !== $old;
    }

    /**
     * @psalm-suppress MixedAssignment
     *
     * @param mixed|null $default
     */
    public function oldInput(string $key = null, $default = null)
    {
        $old = $this->get('_old_input', []);

        if (null === $key) {
            return $old;
        }

        if (! is_array($old)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('_old_input must be an associative array.');
            // @codeCoverageIgnoreEnd
        }

        return Arr::get($old, $key, $default);
    }

    public function id(): SessionId
    {
        return $this->id;
    }

    public function invalidate(): void
    {
        $this->rotate();
        $this->flush();
    }

    public function rotate(): void
    {
        $this->checkIfLocked();

        $new_id = SessionId::new();

        // It makes no sense to have a different invalidated ID than the one that was initially loaded.
        // new ids are only saved when the session is saved.
        if (null === $this->invalidated_id) {
            $this->invalidated_id = $this->id;
            $this->recordEvent(new SessionRotated(ReadOnlySession::fromSession($this)));
        }

        $this->id = $new_id;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function flush(): void
    {
        $this->checkIfLocked();
        $internal = $this->get('_sniccowp');
        $this->attributes = [];
        $this->put('_sniccowp', $internal);
    }

    public function keep($keys): void
    {
        $keys = is_array($keys)
            ? $keys
            : [$keys];

        $this->mergeNewFlashes($keys);

        $this->removeFromOldFlashData($keys);
    }

    public function lastActivity(): int
    {
        return $this->last_activity;
    }

    public function lastRotation(): int
    {
        $ts = $this->get('_sniccowp.timestamps.last_rotated');
        if (! is_int($ts)) {
            throw new RuntimeException(
                'The session storage seems corrupted as the value for key [_sniccowp.timestamps.last_rotated] is not an integer.'
            );
        }

        return $ts;
    }

    public function flashNow(string $key, $value): void
    {
        $this->put($key, $value);

        $this->push('_flash.old', $key);
    }

    public function only($keys): array
    {
        return Arr::only($this->attributes, $keys);
    }

    public function putIfMissing(string $key, Closure $callback): void
    {
        if ($this->missing($key)) {
            $this->put($key, $callback());
        }
    }

    public function missing($keys): bool
    {
        return ! $this->exists($keys);
    }

    public function exists($keys): bool
    {
        $keys = Arr::toArray($keys);

        foreach ($keys as $key) {
            if (! Arr::has($this->attributes, $key)) {
                return false;
            }
        }

        return true;
    }

    public function reflash(): void
    {
        $arr = $this->oldFlashes();
        $this->mergeNewFlashes($arr);

        $this->put('_flash.old', []);
    }

    public function releaseEvents(): array
    {
        $events = $this->stored_events;
        $this->stored_events = [];

        return $events;
    }

    public function remove($keys): void
    {
        $this->checkIfLocked();
        Arr::remove($this->attributes, $keys);
    }

    public function saveUsing(
        SessionDriver $driver,
        Serializer $serializer,
        string $hashed_validator,
        int $current_timestamp
    ): void {
        $this->last_activity = $current_timestamp;

        if (! $this->isDirty()) {
            $driver->touch($this->id->selector(), $this->last_activity);
        } else {
            if ($this->invalidated_id instanceof SessionId) {
                $driver->destroy($this->invalidated_id->selector());
                $this->put('_sniccowp.timestamps.last_rotated', $this->last_activity);
            }

            $this->ageFlashData();

            $serialized_session = SerializedSession::fromString(
                $serializer->serialize($this->attributes),
                $hashed_validator,
                $this->last_activity,
                $this->userId(),
            );

            $driver->write($this->id->selector(), $serialized_session);
        }

        $this->lock();
    }

    public function setUserId($user_id): void
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (! is_string($user_id) && ! is_int($user_id)) {
            throw new InvalidArgumentException('$user_id must be string or integer.');
        }

        $this->put('_user_id', $user_id);
    }

    public function userId()
    {
        /** @var mixed $user_id */
        $user_id = $this->get('_user_id');
        if (is_string($user_id)) {
            return $user_id;
        }

        if (is_int($user_id)) {
            return $user_id;
        }

        if (null === $user_id) {
            return null;
        }

        throw new InvalidArgumentException('$user_id must be string or integer.');
    }

    public function isNew(): bool
    {
        return $this->is_new;
    }

    /**
     * @interal
     */
    public function isDirty(): bool
    {
        if ($this->is_new) {
            return true;
        }

        $is_dirty = $this->attributes !== $this->original_attributes;
        if ($is_dirty) {
            return true;
        }

        if (null !== $this->invalidated_id) {
            return true;
        }

        return [] !== $this->oldFlashes();
    }

    /**
     * @throws SessionIsLocked
     */
    private function checkIfLocked(): void
    {
        if ($this->locked) {
            throw new SessionIsLocked();
        }
    }

    private function removeFromOldFlashData(array $keys): void
    {
        $this->put('_flash.old', array_diff($this->oldFlashes(), $keys));
    }

    /**
     * @return string[]
     * @psalm-suppress MixedReturnTypeCoercion
     */
    private function oldFlashes(): array
    {
        $old = Arr::get($this->attributes, '_flash.old', []);
        if (! is_array($old)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('_flash.old must be an array of strings.');
            // @codeCoverageIgnoreEnd
        }

        return $old;
    }

    private function recordEvent(object $event): void
    {
        $this->stored_events[] = $event;
    }

    /**
     * @param string[] $keys
     */
    private function mergeNewFlashes(array $keys): void
    {
        $new = $this->get('_flash.new', []);

        if (! is_array($new)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('_flash.new must be an array of strings.');
            // @codeCoverageIgnoreEnd
        }

        $values = array_unique(array_merge($new, $keys));

        $this->put('_flash.new', $values);
    }

    private function ageFlashData(): void
    {
        $this->remove($this->oldFlashes());

        $this->put('_flash.old', $this->get('_flash.new', []));

        $this->put('_flash.new', []);
    }

    private function lock(): void
    {
        $this->locked = true;
    }
}
