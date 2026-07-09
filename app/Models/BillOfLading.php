<?php

namespace App\Models;

use App\Services\BillOfLadingWorkflowService;
use Database\Factories\BillOfLadingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'bl_number',
    'booking_number',
    'customer_id',
    'shipment_type',
    'carrier_name',
    'bl_document_type',
    'bl_surrendered',
    'shipment_description',
    'shipper_name',
    'shipper_address',
    'consignee_name',
    'consignee_address',
    'consignee_npwp',
    'notify_party_name',
    'notify_party_address',
    'destination_agent_name',
    'destination_agent_contact',
    'origin',
    'destination',
    'place_of_receipt',
    'port_of_loading',
    'port_of_discharge',
    'place_of_delivery',
    'vessel_name',
    'voyage_number',
    'movement_type',
    'service_type',
    'items_description',
    'goods_description',
    'hs_code',
    'quantity',
    'package_count',
    'container_count_label',
    'gross_weight_kg',
    'volume_cbm',
    'measurement_cbm',
    'marks_and_numbers',
    'free_time_notes',
    'freight_terms',
    'export_reference',
    'input_date',
    'issue_date',
    'place_of_issue',
    'shipped_on_board_date',
    'status',
    'phase',
    'customs_lane',
    'current_milestone_key',
    'gps_tracking_url',
    'note',
    'customer_note',
    'internal_note',
])]
class BillOfLading extends Model
{
    /** @use HasFactory<BillOfLadingFactory> */
    use HasFactory;

    public const TYPE_IMPORT = 'import';

    public const TYPE_EXPORT = 'export';

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

    public const DOCUMENT_TYPES = [
        'original' => 'Original',
        'non_negotiable' => 'Non-Negotiable',
        'copy' => 'Copy',
    ];

    protected function casts(): array
    {
        return [
            'input_date' => 'date',
            'issue_date' => 'date',
            'shipped_on_board_date' => 'date',
            'bl_surrendered' => 'boolean',
            'gross_weight_kg' => 'decimal:2',
            'volume_cbm' => 'decimal:2',
            'measurement_cbm' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BillOfLading $billOfLading): void {
            if (blank($billOfLading->port_of_loading) && filled($billOfLading->origin)) {
                $billOfLading->port_of_loading = $billOfLading->origin;
            }

            if (blank($billOfLading->port_of_discharge) && filled($billOfLading->destination)) {
                $billOfLading->port_of_discharge = $billOfLading->destination;
            }

            if (blank($billOfLading->origin) && filled($billOfLading->port_of_loading)) {
                $billOfLading->origin = $billOfLading->port_of_loading;
            }

            if (blank($billOfLading->destination) && filled($billOfLading->port_of_discharge)) {
                $billOfLading->destination = $billOfLading->port_of_discharge;
            }

            if (blank($billOfLading->goods_description) && filled($billOfLading->items_description)) {
                $billOfLading->goods_description = $billOfLading->items_description;
            }

            if (blank($billOfLading->items_description) && filled($billOfLading->goods_description)) {
                $billOfLading->items_description = $billOfLading->goods_description;
            }

            if (blank($billOfLading->package_count) && filled($billOfLading->quantity)) {
                $billOfLading->package_count = $billOfLading->quantity;
            }

            if (blank($billOfLading->quantity) && filled($billOfLading->package_count)) {
                $billOfLading->quantity = $billOfLading->package_count;
            }

            if (blank($billOfLading->measurement_cbm) && filled($billOfLading->volume_cbm)) {
                $billOfLading->measurement_cbm = $billOfLading->volume_cbm;
            }

            if (blank($billOfLading->volume_cbm) && filled($billOfLading->measurement_cbm)) {
                $billOfLading->volume_cbm = $billOfLading->measurement_cbm;
            }

            if (blank($billOfLading->shipment_type)) {
                $billOfLading->shipment_type = self::TYPE_IMPORT;
            }

            if (blank($billOfLading->note) && filled($billOfLading->customer_note)) {
                $billOfLading->note = $billOfLading->customer_note;
            }

            if (blank($billOfLading->customer_note) && filled($billOfLading->note)) {
                $billOfLading->customer_note = $billOfLading->note;
            }
        });

        static::created(function (BillOfLading $billOfLading): void {
            if ($billOfLading->milestoneStates()->exists()) {
                return;
            }

            app(BillOfLadingWorkflowService::class)->seedInitialMilestones($billOfLading);
        });
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
     * @return HasMany<BillOfLadingContainer, $this>
     */
    public function containers(): HasMany
    {
        return $this->hasMany(BillOfLadingContainer::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<BillOfLadingMilestoneState, $this>
     */
    public function milestoneStates(): HasMany
    {
        return $this->hasMany(BillOfLadingMilestoneState::class)->orderBy('sequence');
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

    public function shipmentTypeLabel(): string
    {
        return config("bl_workflows.shipment_types.{$this->shipment_type}", ucfirst((string) $this->shipment_type));
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
        $this->update([
            'status' => $attributes['status'],
            'note' => $attributes['note'] ?? null,
            'customer_note' => $attributes['note'] ?? $this->customer_note,
        ]);

        return $this->updates()->create([
            'user_id' => $userId ?? auth()->id(),
            'status' => $attributes['status'],
            'phase' => $this->phase,
            'milestone_key' => $this->current_milestone_key,
            'customs_lane' => $this->customs_lane,
            'visibility' => $attributes['visibility'] ?? BillOfLadingUpdate::VISIBILITY_CUSTOMER,
            'note' => $attributes['note'] ?? null,
        ]);
    }
}
