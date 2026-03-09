<?php

namespace App\Traits;

use App\Models\User\Role;
use App\Models\User\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasPermissions
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_user',
            'user_id',
            'role_id'
        );
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_user',
            'user_id',
            'permission_id'
        )->withPivot('allowed');
    }

    public function hasPermission(string $code): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $userPermission = $this->permissions
            ->firstWhere('code', $code);

        if ($userPermission) {
            return (bool) $userPermission->pivot->allowed;
        }

        foreach ($this->roles as $role) {
            if (
                $role->permissions
                    ->contains('code', $code)
            ) {
                return true;
            }
        }

        return false;
    }
    
    public function hasRole(string|array $roles): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return $this->roles
            ->pluck('code')
            ->intersect($roles)
            ->isNotEmpty();
    }
}
