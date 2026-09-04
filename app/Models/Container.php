<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Database\Factories\ContainerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'bill_of_lading_id',
    'container_number',
    'seal_number',
    'driver_name',
    'license_number',
    'driver_tracking_url',
    'photo_door_path',
    'photo_floor_path',
    'photo_eir_path',
    'photo_seal_path',
    'stuffing_progress',
    'gate_in_cy_at',
    'gate_in_pol',
    'vgm_kg',
    'final_checked',
    'final_checked_at',
    'container_type',
    'package_count',
    'gross_weight_kg',
    'measurement_cbm',
    'sort_order',
])]
class Container extends Model
{
    /** @use HasFactory<ContainerFactory> */
    use HasFactory, LogsChanges;

    public const STUFFING_ON_PROCESS = 'on_process';

    public const STUFFING_FINISHED = 'finished';

    public const STUFFING_PROGRESS = [
        self::STUFFING_ON_PROCESS => 'ON-PROCESS',
        self::STUFFING_FINISHED => 'FINISHED',
    ];

    protected static function booted(): void
    {
        static::created(fn (Container $container) => $container->recordAudit('container_created'));
        static::updated(fn (Container $container) => $container->recordAudit('container_updated'));
        static::deleted(fn (Container $container) => $container->recordAudit('container_deleted'));
    }

    protected function casts(): array
    {
        return [
            'gross_weight_kg' => 'decimal:3',
            'measurement_cbm' => 'decimal:4',
            'vgm_kg' => 'decimal:3',
            'sort_order' => 'integer',
            'gate_in_cy_at' => 'datetime',
            'final_checked_at' => 'date',
            'final_checked' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<BillOfLading, $this>
     */
    public function billOfLading(): BelongsTo
    {
        return $this->belongsTo(BillOfLading::class);
    }

    protected function changeLogSubject(): string
    {
        return 'container '.($this->container_number ?: '#'.$this->getKey());
    }

    public function stuffingProgressLabel(): string
    {
        return self::STUFFING_PROGRESS[$this->stuffing_progress] ?? '-';
    }

    public function photoUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    /**
     * @return array<string, string|null>
     */
    public function documentationPhotos(): array
    {
        return [
            'Pintu' => $this->photoUrl($this->photo_door_path),
            'Lantai' => $this->photoUrl($this->photo_floor_path),
            'EIR' => $this->photoUrl($this->photo_eir_path),
            'Seal' => $this->photoUrl($this->photo_seal_path),
        ];
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
