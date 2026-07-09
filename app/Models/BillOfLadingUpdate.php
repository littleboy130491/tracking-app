<?php

namespace App\Models;

use Database\Factories\BillOfLadingUpdateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'bill_of_lading_id',
    'user_id',
    'status',
    'phase',
    'milestone_key',
    'customs_lane',
    'visibility',
    'note',
])]
class BillOfLadingUpdate extends Model
{
    /** @use HasFactory<BillOfLadingUpdateFactory> */
    use HasFactory;

    public const VISIBILITY_CUSTOMER = 'customer';

    public const VISIBILITY_ADMIN_ONLY = 'admin_only';

    /**
     * @return BelongsTo<BillOfLading, $this>
     */
    public function billOfLading(): BelongsTo
    {
        return $this->belongsTo(BillOfLading::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCustomerVisible(): bool
    {
        return ($this->visibility ?? self::VISIBILITY_CUSTOMER) === self::VISIBILITY_CUSTOMER;
    }
}
