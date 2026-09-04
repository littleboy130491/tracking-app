<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'pic_name',
    'pic_phone',
    'last_login_at',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if (BillOfLading::withTrashed()->accessibleBy($user)->exists()) {
                throw ValidationException::withMessages([
                    'user' => 'Customer accounts with BL history cannot be deleted. Deactivate the account instead.',
                ]);
            }
        });
    }

    public const ROLE_ADMIN = 'super_admin';

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_PANEL_USER = 'panel_user';

    public const WORKFLOW_ROLES = [
        'workflow_documents',
        'workflow_customs',
        'workflow_billing',
        'workflow_operations',
        'workflow_export',
        'workflow_delivery',
    ];

    public const WORKFLOW_ROLE_LABELS = [
        'workflow_documents' => 'Document intake',
        'workflow_customs' => 'Customs',
        'workflow_billing' => 'Billing',
        'workflow_operations' => 'Operations',
        'workflow_export' => 'Export',
        'workflow_delivery' => 'Delivery',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
            && $this->is_active
            && $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_PANEL_USER]);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(self::ROLE_CUSTOMER);
    }

    public function canManageMilestone(?string $milestoneKey): bool
    {
        if ($this->hasRole(self::ROLE_ADMIN)) {
            return true;
        }

        if (blank($milestoneKey)) {
            return $this->hasRole(self::ROLE_PANEL_USER);
        }

        $role = config("bl_workflows.milestone_roles.{$milestoneKey}");

        return filled($role) && $this->hasRole($role);
    }

    /**
     * @return BelongsToMany<Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withTimestamps()->orderBy('companies.name');
    }

    /**
     * @return Builder<BillOfLading>
     */
    public function accessibleBillOfLadings(): Builder
    {
        return BillOfLading::query()->accessibleBy($this);
    }
}
