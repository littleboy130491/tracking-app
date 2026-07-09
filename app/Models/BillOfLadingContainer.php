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
    'goods_description',
    'sort_order',
])]
class BillOfLadingContainer extends Model
{
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
}
