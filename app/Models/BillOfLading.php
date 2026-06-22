<?php

namespace App\Models;

use Database\Factories\BillOfLadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'bl_number',
    'customer_id',
    'shipment_description',
    'origin',
    'destination',
    'items_description',
    'quantity',
    'gross_weight_kg',
    'volume_cbm',
    'input_date',
    'status',
    'phase',
    'gps_tracking_url',
    'note',
])]
class BillOfLading extends Model
{
    /** @use HasFactory<BillOfLadingFactory> */
    use HasFactory;

    public const STATUSES = [
        'Pending',
        'In Progress',
        'On Hold',
        'Completed',
    ];

    public const PHASES = [
        'Input',
        'Customs',
        'Transit',
        'Delivery',
        'Closed',
    ];

    protected function casts(): array
    {
        return [
            'input_date' => 'date',
            'gross_weight_kg' => 'decimal:2',
            'volume_cbm' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return HasMany<BillOfLadingUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(BillOfLadingUpdate::class)->oldest();
    }

    /**
     * @param  array{status: string, phase: string, note?: string|null}  $attributes
     */
    public function postProgressUpdate(array $attributes, ?int $userId = null): BillOfLadingUpdate
    {
        $this->update([
            'status' => $attributes['status'],
            'phase' => $attributes['phase'],
            'note' => $attributes['note'] ?? null,
        ]);

        return $this->updates()->create([
            'user_id' => $userId ?? auth()->id(),
            'status' => $attributes['status'],
            'phase' => $attributes['phase'],
            'note' => $attributes['note'] ?? null,
        ]);
    }
}
