<?php

namespace App\Models;

use Database\Factories\LogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'loggable_type',
    'loggable_id',
    'user_id',
    'event',
    'description',
    'changes',
])]
class Log extends Model
{
    /** @use HasFactory<LogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_DELETED = 'deleted';

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whoLabel(): string
    {
        if ($this->user) {
            return $this->user->pic_name ?: $this->user->name ?: $this->user->email;
        }

        return 'Sistem';
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            self::EVENT_CREATED => 'Dibuat',
            self::EVENT_UPDATED => 'Diperbarui',
            self::EVENT_DELETED => 'Dihapus',
            default => $this->event,
        };
    }
}
