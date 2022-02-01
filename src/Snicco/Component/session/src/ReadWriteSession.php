<?php

declare(strict_types=1);

namespace Snicco\Component\Session;

use Closure;
use DateTimeImmutable;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Event\SessionRotated;
use Snicco\Component\Session\Exception\SessionIsLocked;
use Snicco\Component\Session\SessionManager\SessionManager;
use Snicco\Component\Session\ValueObject\CsrfToken;
use Snicco\Component\Session\ValueObject\ReadOnlySession;
use Snicco\Component\Session\ValueObject\SerializedSessionData;
use Snicco\Component\Session\ValueObject\SessionId;
use Snicco\Component\StrArr\Arr;

use function array_diff;
use function array_merge;
use function array_unique;
use function count;
use function filter_var;
use function func_get_args;
use function is_array;
use function is_null;
use function md5;

/**
 * @interal Either depend on {@see MutableSession} or {@see ImmutableSession} depending on your use
 *          case.
 */
final class ReadWriteSession implements Session
{

    private SessionId $id;
    private DateTimeImmutable $last_activity;
    private array $attributes;
    private array $original_attributes;
    private bool $locked = false;
    private bool $is_new = false;
    private ?SessionId $invalidated_id = null;

    /**
     * @var <array,object>
     */
    private $stored_events = [];

    /**
     * @interal Sessions MUST only be started from a {@see SessionManager}
     */
    public function __construct(SessionId $id, array $data, DateTimeImmutable $last_activity)
    {
        $this->id = $id;
        $this->attributes = $data;

        if (!$this->has('_sniccowp.timestamps.created_at')) {
            $this->put('_sniccowp.timestamps.created_at', $last_activity->getTimestamp());
            $this->is_new = true;
        }
        if (!$this->has('_sniccowp.timestamps.last_rotated')) {
            $this->put('_sniccowp.timestamps.last_rotated', $last_activity->getTimestamp());
        }
        if (!$this->has('_sniccowp.csrf_token')) {
            $this->refreshCsrfToken();
        }

        $this->original_attributes = $this->attributes;
        $this->last_activity = $last_activity;
    }

    public function has(string $key): bool
    {
        return Arr::get($this->attributes, $key) !== null;
    }

    public function put($key, $value = null): void
    {
        $this->checkLocked();

        if (!is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $array_key => $array_value) {
            Arr::set($this->attributes, $array_key, $array_value);
        }
    }

    /**
     * @throws SessionIsLocked
     */
    private function checkLocked(): void
    {
        if ($this->locked) {
            throw new SessionIsLocked();
        }
    }

    private function refreshCsrfToken(): void
    {
        $this->put('_sniccowp.csrf_token', md5($this->id->asString()));
    }

    public function all(): array
    {
        return Arr::except($this->attributes, '_sniccowp');
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param mixed $default
     */
    public function get(string $key, $default = null)
    {
        return Arr::get($this->attributes, $key, $default);
    }

    public function createdAt(): int
    {
        return $this->get('_sniccowp.timestamps.created_at');
    }

    public function csrfToken(): CsrfToken
    {
        return new CsrfToken($this->get('_sniccowp.csrf_token'));
    }

    public function decrement(string $key, int $amount = 1): void
    {
        $this->increment($key, $amount * -1);
    }

    public function increment(string $key, int $amount = 1, int $start_value = 0): void
    {
        if (!$this->has($key)) {
            $this->put($key, $start_value);
        }

        $this->put($key, $this->get($key, 0) + $amount);
    }

    public function flashInput(array $input): void
    {
        $this->checkLocked();
        $this->flash('_old_input', $input);
    }

    public function flash(string $key, $value = true): void
    {
        $this->checkLocked();
        $this->put($key, $value);

        $this->push('_flash.new', $key);

        $this->removeFromOldFlashData([$key]);
    }

    public function push(string $key, $value): void
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->put($key, $array);
    }

    private function removeFromOldFlashData(array $keys): void
    {
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
    }

    public function hasOldInput(string $key = null): bool
    {
        $old = $this->oldInput($key);

        return is_null($key)
            ? count($old) > 0
            : !is_null($old);
    }

    /**
     * @param mixed|null $default
     */
    public function oldInput(string $key = null, $default = null)
    {
        $old = $this->get('_old_input', []);

        if (null === $key) {
            return $old;
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
        $this->checkLocked();
        $this->invalidated_id = $this->id;
        $this->id = SessionId::createFresh();
        $this->refreshCsrfToken();
        $this->recordEvent(new SessionRotated(ReadOnlySession::fromSession($this)));
    }

    private function recordEvent(object $event): void
    {
        $this->stored_events[] = $event;
    }

    public function flush(): void
    {
        $this->checkLocked();
        $internal = $this->get('_sniccowp');
        $this->attributes = [];
        $this->put('_sniccowp', $internal);
    }

    public function keep($keys = null): void
    {
        $this->mergeNewFlashes(
            $keys = is_array($keys)
                ? $keys
                : func_get_args()
        );

        $this->removeFromOldFlashData($keys);
    }

    private function mergeNewFlashes(array $keys): void
    {
        $values = array_unique(array_merge($this->get('_flash.new', []), $keys));

        $this->put('_flash.new', $values);
    }

    public function lastActivity(): int
    {
        return $this->last_activity->getTimestamp();
    }

    public function lastRotation(): int
    {
        return $this->get('_sniccowp.timestamps.last_rotated');
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
        return !$this->exists($keys);
    }

    public function exists($keys): bool
    {
        $keys = Arr::toArray($keys);

        foreach ($keys as $key) {
            if (!Arr::has($this->attributes, $key)) {
                return false;
            }
        }

        return true;
    }

    public function reflash(): void
    {
        $this->mergeNewFlashes($this->get('_flash.old', []));

        $this->put('_flash.old', []);
    }

    public function releaseEvents(): array
    {
        $events = $this->stored_events;
        $this->stored_events = [];
        return $events;
    }

    public function remove(string $key): void
    {
        $this->checkLocked();
        Arr::forget($this->attributes, $key);
    }

    public function replace(array $attributes): void
    {
        $this->put($attributes);
    }

    public function saveUsing(SessionDriver $driver, DateTimeImmutable $now): void
    {
        $this->last_activity = $now;

        if (!$this->isDirty()) {
            $driver->touch($this->id->asHash(), $now);
        } else {
            if ($this->invalidated_id instanceof SessionId) {
                $driver->destroy([$this->invalidated_id->asHash()]);
                $this->put('_sniccowp.timestamps.last_rotated', $now->getTimestamp());
            }

            $this->ageFlashData();

            $driver->write(
                $this->id->asHash(),
                SerializedSessionData::fromArray(
                    $this->attributes,
                    $this->last_activity->getTimestamp(),
                ),
            );
        }

        $this->lock();
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

        if ($this->invalidated_id instanceof SessionId) {
            return true;
        }

        return count(Arr::get($this->attributes, '_flash.old', [])) > 0;
    }

    private function ageFlashData(): void
    {
        $this->forget($this->get('_flash.old', []));

        $this->put('_flash.old', $this->get('_flash.new', []));

        $this->put('_flash.new', []);
    }

    public function forget($keys): void
    {
        $this->checkLocked();
        Arr::forget($this->attributes, $keys);
    }

    private function lock(): void
    {
        $this->locked = true;
    }

}