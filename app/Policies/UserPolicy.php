<?php

namespace App\Policies;

use App\Models\Agency\Agency;
use App\Models\User\User;

class UserPolicy
{
    public function viewAny(User $auth)
    {
        return $auth->hasRole(['admin', 'sub_admin']);
    }

    public function create(User $auth, array $data)
    {
        if ($auth->hasRole('admin')) {
            return in_array($data['role_code'], ['sub_admin', 'reporter']);
        }

        if ($auth->hasRole('sub_admin')) {
            if ($data['role_code'] !== 'reporter') {
                return false;
            }

            return Agency::where('id', $data['agency_id'])
                ->where('parent_agency_id', $auth->agency_id)
                ->exists();
        }

        return false;
    }

    public function update(User $auth, User $target)
    {
        if ($auth->hasRole('admin')) {
            return $target->hasRole(['sub_admin', 'reporter','supervisor']);
        }

        if ($auth->hasRole('sub_admin')) {

            if (! $target->hasRole('reporter')) {
                return false;
            }

            return Agency::where('id', $target->agency_id)
                ->where('parent_agency_id', $auth->agency_id)
                ->exists();
        }

        return false;
    }

    public function delete(User $auth, User $target)
    {
        // Không ai được xóa chính mình
        if ($auth->id === $target->id) {
            return false;
        }

        // Chỉ admin được xóa
        if ($auth->hasRole('admin')) {
            return ! $target->hasRole('admin');
        }

        // Sub_admin & reporter: không được xóa ai
        return false;
    }

}
