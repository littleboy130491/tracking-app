<?php

namespace App\Models\Concerns;

use App\Models\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait LogsChanges
{
    public static function bootLogsChanges(): void
    {
        static::created(function (Model $model): void {
            $model->recordChangeLog(Log::EVENT_CREATED);
        });

        static::updated(function (Model $model): void {
            $model->recordChangeLog(Log::EVENT_UPDATED);
        });

        static::deleted(function (Model $model): void {
            $model->recordChangeLog(Log::EVENT_DELETED);
        });
    }

    /**
     * @return MorphMany<Log, $this>
     */
    public function logs(): MorphMany
    {
        return $this->morphMany(Log::class, 'loggable')->latest('created_at');
    }

    /**
     * @param  array<string, mixed>|null  $changes
     */
    public function recordChangeLog(string $event, ?array $changes = null, ?int $userId = null): ?Log
    {
        $changes ??= $this->changeLogPayload($event);

        if ($event === Log::EVENT_UPDATED && $changes === []) {
            return null;
        }

        return $this->logs()->create([
            'user_id' => $userId ?? auth()->id(),
            'event' => $event,
            'description' => $this->changeLogDescription($event, $changes),
            'changes' => $changes ?: null,
        ]);
    }

    /**
     * @return list<string>
     */
    protected function changeLogIgnoredAttributes(): array
    {
        return [
            'id',
            'password',
            'remember_token',
            'created_at',
            'updated_at',
            'deleted_at',
            'last_login_at',
            'email_verified_at',
        ];
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}|mixed>
     */
    protected function changeLogPayload(string $event): array
    {
        $ignored = $this->changeLogIgnoredAttributes();

        if ($event === Log::EVENT_CREATED) {
            return collect($this->attributesToArray())
                ->except($ignored)
                ->all();
        }

        if ($event === Log::EVENT_DELETED) {
            return collect($this->getOriginal())
                ->except($ignored)
                ->all();
        }

        return collect($this->getChanges())
            ->except($ignored)
            ->mapWithKeys(fn (mixed $value, string $key): array => [
                $key => [
                    'old' => $this->getRawOriginal($key),
                    'new' => $value,
                ],
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    protected function changeLogDescription(string $event, array $changes): string
    {
        $subject = $this->changeLogSubject();

        if ($event === Log::EVENT_CREATED) {
            return "Created {$subject}";
        }

        if ($event === Log::EVENT_DELETED) {
            return "Deleted {$subject}";
        }

        $fields = collect(array_keys($changes))->sort()->values();

        if ($fields->isEmpty()) {
            return "Updated {$subject}";
        }

        return "Updated {$subject}: ".$fields->join(', ');
    }

    protected function changeLogSubject(): string
    {
        return class_basename($this);
    }
}
