<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Models\User\User;
use App\Models\User\Role;
use App\Models\User\Permission;
use App\Models\Agency\Agency;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // 1) Roles
            $super = Role::firstOrCreate(
                ['code' => 'super_admin'],
                ['name' => 'Super Admin']
            );

            $agencyAdmin = Role::firstOrCreate(
                ['code' => 'agency_admin'],
                ['name' => 'Admin Sở/Ban/Ngành']
            );

            $unitAdmin = Role::firstOrCreate(
                ['code' => 'unit_admin'],
                ['name' => 'Admin Phòng/Ban/Đơn vị']
            );

            // 2) Permissions (NHỚ: module là bắt buộc)
            $perms = [
                // users
                ['code' => 'users.view',   'name' => 'USERS VIEW',   'module' => 'users'],
                ['code' => 'users.create', 'name' => 'USERS CREATE', 'module' => 'users'],
                ['code' => 'users.edit',   'name' => 'USERS EDIT',   'module' => 'users'],
                ['code' => 'users.delete', 'name' => 'USERS DELETE', 'module' => 'users'],

                // roles
                ['code' => 'roles.view',   'name' => 'ROLES VIEW',   'module' => 'roles'],
                ['code' => 'roles.manage', 'name' => 'ROLES MANAGE', 'module' => 'roles'],

                // agencies
                ['code' => 'agencies.view',   'name' => 'AGENCIES VIEW',   'module' => 'agencies'],
                ['code' => 'agencies.manage', 'name' => 'AGENCIES MANAGE', 'module' => 'agencies'],
            ];

            $permIds = [];
            foreach ($perms as $p) {
                $perm = Permission::firstOrCreate(
                    ['code' => $p['code']],
                    ['name' => $p['name'], 'module' => $p['module']]
                );
                $permIds[$p['code']] = $perm->id;
            }

            // 3) Gán permission cho role
            // super_admin: full
            $super->permissions()->sync(array_values($permIds));

            // agency_admin: quản lý user trong phạm vi sở
            $agencyAdmin->permissions()->sync([
                $permIds['users.view'],
                $permIds['users.create'],
                $permIds['users.edit'],
                $permIds['agencies.view'],
            ]);

            // unit_admin: chỉ xem + sửa user trong đơn vị
            $unitAdmin->permissions()->sync([
                $permIds['users.view'],
                $permIds['users.edit'],
            ]);

        
            // 5) Super Admin user mẫu
            $admin = User::firstOrCreate(
                ['username' => 'superadmin'],
                [
                    'password' => Hash::make('123456'),
                    'full_name' => 'Super Admin',
                    'email' => 'superadmin@ioc.local',
                    'agency_id' => null,
                    'is_active' => 1,
                ]
            );

            $admin->roles()->sync([$super->id]);
        });
    }
}
