<?php

namespace App\Models\User;

use App\Models\Agency\Agency;
use App\Models\Mission\Mission;
use App\Traits\HasPermissions;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, HasPermissions;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'password',
        'full_name',
        'email',
        'agency_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_user',
            'user_id',
            'permission_id'
        )->withPivot('allowed');
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function missions()
    {
        return $this->hasMany(Mission::class, 'created_by');
    }
}
