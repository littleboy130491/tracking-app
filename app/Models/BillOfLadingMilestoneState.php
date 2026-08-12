<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'bill_of_lading_id',
    'workflow_key',
    'milestone_key',
    'sequence',
    'label',
    'customer_label',
    'state',
    'completed_at',
    'customer_visible',
])]
class BillOfLadingMilestoneState extends Model
{
    public const STATE_PENDING = 'pending';

    public const STATE_IN_PROGRESS = 'in_progress';

    public const STATE_COMPLETED = 'completed';

    public const STATE_SKIPPED = 'skipped';

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'completed_at' => 'datetime',
            'customer_visible' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<BillOfLading, $this>
     */
    public function billOfLading(): BelongsTo
    {
        return $this->belongsTo(BillOfLading::class);
    }

    public function displayLabel(bool $forCustomer = false): string
    {
        if ($forCustomer && filled($this->customer_label)) {
            return $this->customer_label;
        }

        return $this->label;
    }
}
