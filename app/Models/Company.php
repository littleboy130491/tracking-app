<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'name',
    'address',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Company $company): void {
            if (BillOfLading::withTrashed()->where('company_id', $company->id)->exists()) {
                throw ValidationException::withMessages([
                    'company' => 'Companies with BL history cannot be deleted.',
                ]);
            }
        });
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps()->orderBy('users.name');
    }

    /**
     * @return HasMany<BillOfLading, $this>
     */
    public function billOfLadings(): HasMany
    {
        return $this->hasMany(BillOfLading::class);
    }
}
