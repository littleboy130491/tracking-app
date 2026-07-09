<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'company_name',
    'company_address',
    'pic_name',
    'pic_phone',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

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
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
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
     * @return HasMany<BillOfLading, $this>
     */
    public function billOfLadings(): HasMany
    {
        return $this->hasMany(BillOfLading::class, 'customer_id');
    }
}
