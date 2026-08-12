<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'bill_of_lading_id',
    'container_number',
    'seal_number',
    'container_type',
    'package_count',
    'gross_weight_kg',
    'measurement_cbm',
    'tare_weight_kg',
    'sort_order',
])]
class BillOfLadingContainer extends Model
{
    protected static function booted(): void
    {
        static::created(fn (BillOfLadingContainer $container) => $container->recordAudit('container_created'));
        static::updated(fn (BillOfLadingContainer $container) => $container->recordAudit('container_updated'));
        static::deleted(fn (BillOfLadingContainer $container) => $container->recordAudit('container_deleted'));
    }

    protected function casts(): array
    {
        return [
            'gross_weight_kg' => 'decimal:3',
            'measurement_cbm' => 'decimal:4',
            'tare_weight_kg' => 'decimal:3',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<BillOfLading, $this>
     */
    public function billOfLading(): BelongsTo
    {
        return $this->belongsTo(BillOfLading::class);
    }

    private function recordAudit(string $event): void
    {
        $billOfLading = $this->billOfLading()->first();

        if (! $billOfLading) {
            return;
        }

        $billOfLading->recordAudit($event, [
            'container' => [
                'old' => $event === 'container_created' ? null : $this->getRawOriginal(),
                'new' => $event === 'container_deleted' ? null : $this->attributesToArray(),
            ],
        ]);
    }
}
