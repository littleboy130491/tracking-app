<?php

namespace App\Models;

use App\Services\BillOfLadingWorkflowService;
use Database\Factories\BillOfLadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'bl_number',
    'aju_number',
    'company_id',
    'shipment_type',
    'shipping_method',
    'carrier_name',
    'shipment_description',
    'shipper_name',
    'consignee_name',
    'consignee_address',
    'notify_party_name',
    'destination_agent_name',
    'port_of_loading',
    'port_of_discharge',
    'place_of_delivery',
    'vessel_name',
    'voyage_number',
    'goods_description',
    'hs_code',
    'package_count',
    'gross_weight_kg',
    'measurement_cbm',
    'free_time_notes',
    'input_date',
    'retention_until',
    'shipped_on_board_date',
    'status',
    'phase',
    'customs_lane',
    'current_milestone_key',
    'gps_tracking_url',
    'customer_note',
])]
class BillOfLading extends Model
{
    /** @use HasFactory<BillOfLadingFactory> */
    use HasFactory, SoftDeletes;

    public const TYPE_IMPORT = 'import';

    public const TYPE_EXPORT = 'export';

    public const SHIPPING_METHOD_FCL = 'fcl';

    public const SHIPPING_METHOD_LCL = 'lcl';

    public const SHIPPING_METHOD_AIR = 'air';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_IN_PROGRESS = 'In Progress';

    public const STATUS_ON_HOLD = 'On Hold';

    public const STATUS_COMPLETED = 'Completed';

    public const STATUS_CANCELLED = 'Cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_ON_HOLD,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /** @deprecated Kept for backward-compatible filters/tests during transition */
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
            'retention_until' => 'date',
            'shipped_on_board_date' => 'date',
            'gross_weight_kg' => 'decimal:2',
            'measurement_cbm' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BillOfLading $billOfLading): void {
            if (
                $billOfLading->exists
                && $billOfLading->isDirty('shipment_type')
                && $billOfLading->milestoneStates()->exists()
            ) {
                throw ValidationException::withMessages([
                    'shipment_type' => 'Shipment type cannot be changed after workflow tracking has started.',
                ]);
            }

            if (blank($billOfLading->retention_until)) {
                $inputDate = Carbon::parse($billOfLading->input_date ?? today())->startOfDay();
                $retentionStart = $inputDate->greaterThan(today()) ? $inputDate : today();
                $billOfLading->retention_until = $retentionStart->addYears(
                    (int) config('bl_workflows.retention_years', 3)
                );
            }

            if (
                $billOfLading->exists
                && $billOfLading->status === self::STATUS_COMPLETED
                && $billOfLading->isDirty('status')
                && $billOfLading->milestoneStates()
                    ->whereIn('state', [
                        BillOfLadingMilestoneState::STATE_PENDING,
                        BillOfLadingMilestoneState::STATE_IN_PROGRESS,
                    ])
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'status' => 'A BL can only be completed after all active workflow milestones are complete.',
                ]);
            }

            if (blank($billOfLading->shipment_type)) {
                $billOfLading->shipment_type = self::TYPE_IMPORT;
            }

            if (blank($billOfLading->shipping_method)) {
                $billOfLading->shipping_method = self::SHIPPING_METHOD_FCL;
            }
        });

        static::created(function (BillOfLading $billOfLading): void {
            if ($billOfLading->milestoneStates()->exists()) {
                return;
            }

            app(BillOfLadingWorkflowService::class)->seedInitialMilestones($billOfLading);

            $billOfLading->recordAudit('created', [
                'record' => ['old' => null, 'new' => $billOfLading->attributesToArray()],
            ]);
        });

        static::updated(function (BillOfLading $billOfLading): void {
            $changes = collect($billOfLading->getChanges())
                ->except(['updated_at'])
                ->mapWithKeys(fn (mixed $value, string $key): array => [
                    $key => [
                        'old' => $billOfLading->getRawOriginal($key),
                        'new' => $value,
                    ],
                ])
                ->all();

            if ($changes !== []) {
                $billOfLading->recordAudit('updated', $changes);
            }
        });

        static::deleting(function (BillOfLading $billOfLading): void {
            if ($billOfLading->isWithinRetentionWindow()) {
                throw ValidationException::withMessages([
                    'retention' => 'This BL is retained until '.$billOfLading->retentionExpiresAt()?->toDateString().'.',
                ]);
            }

            $billOfLading->recordAudit(
                $billOfLading->isForceDeleting() ? 'force_deleted' : 'deleted',
                ['deleted_at' => ['old' => null, 'new' => now()->toIso8601String()]],
            );
        });
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @param  Builder<BillOfLading>  $query
     * @return Builder<BillOfLading>
     */
    public function scopeAccessibleBy($query, User $user)
    {
        return $query->whereIn(
            'company_id',
            $user->companies()->select('companies.id'),
        );
    }

    /**
     * @return HasMany<BillOfLadingUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(BillOfLadingUpdate::class)->oldest();
    }

    /**
     * @return HasMany<Container, $this>
     */
    public function containers(): HasMany
    {
        return $this->hasMany(Container::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<BillOfLadingMilestoneState, $this>
     */
    public function milestoneStates(): HasMany
    {
        return $this->hasMany(BillOfLadingMilestoneState::class)->orderBy('sequence');
    }

    /**
     * @return HasMany<BillOfLadingAudit, $this>
     */
    public function audits(): HasMany
    {
        return $this->hasMany(BillOfLadingAudit::class)->latest('created_at');
    }

    public function currentMilestone(): ?BillOfLadingMilestoneState
    {
        return $this->milestoneStates
            ->firstWhere('state', BillOfLadingMilestoneState::STATE_IN_PROGRESS)
            ?? $this->milestoneStates->firstWhere('milestone_key', $this->current_milestone_key);
    }

    public function customsLaneLabel(): ?string
    {
        if (blank($this->customs_lane)) {
            return null;
        }

        return config("bl_workflows.customs_lanes.{$this->customs_lane}");
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_IN_PROGRESS => 'Sedang diproses',
            self::STATUS_ON_HOLD => 'Ditahan',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => (string) $status,
        };
    }

    public function displayStatus(): string
    {
        return self::statusLabel($this->status);
    }

    public function shipmentTypeLabel(): string
    {
        return config("bl_workflows.shipment_types.{$this->shipment_type}", ucfirst((string) $this->shipment_type));
    }

    public function shippingMethodLabel(): string
    {
        return config("bl_workflows.shipping_methods.{$this->shipping_method}", strtoupper((string) $this->shipping_method));
    }

    /**
     * @return array<string, string>
     */
    public static function milestoneOptions(): array
    {
        $groups = [
            config('bl_workflows.import_pre_lane', []),
            ...array_values(config('bl_workflows.import_lanes', [])),
            config('bl_workflows.export', []),
            config('bl_workflows.delivery', []),
        ];

        return collect($groups)
            ->flatten(1)
            ->mapWithKeys(fn (array $milestone): array => [
                $milestone['key'] => $milestone['label'],
            ])
            ->all();
    }

    public function retentionExpiresAt(): ?Carbon
    {
        if ($this->retention_until) {
            return $this->retention_until->copy();
        }

        if (! $this->input_date) {
            return null;
        }

        return $this->input_date->copy()->addYears((int) config('bl_workflows.retention_years', 3));
    }

    public function isWithinRetentionWindow(): bool
    {
        $expiresAt = $this->retentionExpiresAt();

        return $expiresAt === null || $expiresAt->isFuture();
    }

    public function canBeDeletedAfterRetention(): bool
    {
        return ! $this->isWithinRetentionWindow();
    }

    /**
     * @param  array{status: string, phase: string, note?: string|null, visibility?: string}  $attributes
     */
    public function postProgressUpdate(array $attributes, ?int $userId = null): BillOfLadingUpdate
    {
        return app(BillOfLadingWorkflowService::class)->postProgressUpdate(
            $this,
            $attributes,
            $userId ?? auth()->id(),
        );
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function recordAudit(string $event, array $changes, ?int $userId = null): void
    {
        BillOfLadingAudit::query()->create([
            'bill_of_lading_id' => $this->getKey(),
            'bl_number' => $this->bl_number,
            'user_id' => $userId ?? auth()->id(),
            'event' => $event,
            'changes' => $changes,
        ]);
    }
}
