<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserRequest;
use App\Models\User\User;
use App\Models\User\Role;
use App\Models\Agency\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $auth = auth()->user();

        $query = User::with(['roles', 'agency.parent']);

        if ($auth->hasRole('sub_admin')) {
            $childAgencyIds = Agency::where(
                'parent_agency_id',
                $auth->agency_id
            )->pluck('id');

            $query->where(function ($q) use ($auth, $childAgencyIds) {
                $q->whereIn('agency_id', $childAgencyIds);
            });
        }

        return view('user.index', [
            'users' => $query->orderBy('id')->get()
        ]);
    }

    public function create()
    {
        $auth = auth()->user();

        if ($auth->hasRole('admin')) {
            $roles = Role::whereIn('code', [
                'supervisor',
                'sub_admin',
                'reporter'
            ])->get();

            $agenciesLevel1 = Agency::whereNull('parent_agency_id')->get();

            return view('user.create', [
                'roles' => $roles,
                'agenciesLevel1' => $agenciesLevel1,
                'mode' => 'admin',
            ]);
        }

        if ($auth->hasRole('sub_admin')) {
            $agencies = Agency::where('parent_agency_id', $auth->agency_id)
                ->orderBy('agency_name')
                ->get();

            return view('user.create', [
                'agencies' => $agencies,
                'mode' => 'sub_admin',
            ]);
        }

        abort(403, 'Bạn không có quyền truy cập chức năng này');
    }


    public function store(UserRequest $request)
    {
        $data = $request->validated();
        $auth = auth()->user();

        if ($auth->hasRole('sub_admin')) {
            $data['role_code'] = 'reporter';
        }

        $this->authorize('create', [User::class, $data]);

        $user = User::create([
            'username' => $data['username'],
            'password' => bcrypt($data['password']),
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'agency_id' => $data['agency_id'] ?? null,
        ]);

        $role = Role::where('code', $data['role_code'])->firstOrFail();
        $user->roles()->attach($role->id);

        return redirect()->route('users.index')
            ->with('success', 'Thêm người dùng thành công');
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        $auth = auth()->user();

        $userRoleCode = $user->roles->first()?->code;

        // agency hiện tại
        $parentAgencyId = null;
        if ($user->agency && $user->agency->parent_agency_id) {
            $parentAgencyId = $user->agency->parent_agency_id;
        }

        /* ---------- ADMIN ---------- */
        if ($auth->hasRole('admin')) {
            return view('user.edit', [
                'user' => $user,
                'roles' => Role::whereIn('code', [
                    'supervisor',
                    'sub_admin',
                    'reporter'
                ])->get(),
                'agenciesLevel1' => Agency::whereNull('parent_agency_id')->get(),
                'userRoleCode' => $userRoleCode,
                'parentAgencyId' => $parentAgencyId,
                'mode' => 'admin',
            ]);
        }

        /* ---------- SUB ADMIN ---------- */
        if ($auth->hasRole('sub_admin')) {
            return view('user.edit', [
                'user' => $user,
                'agencies' => Agency::where('parent_agency_id', $auth->agency_id)
                    ->orderBy('agency_name')
                    ->get(),
                'mode' => 'sub_admin',
            ]);
        }

        abort(403);
    }

    /* ===================== UPDATE ===================== */
    public function update(UserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        $user->update([
            'full_name' => $data['full_name'],
            'email' => $data['email'] ?? null,
            'is_active' => $data['is_active'],
            'agency_id' => $data['agency_id'] ?? null,
        ]);

        // admin mới được đổi role
        if (isset($data['role_code'])) {
            $role = Role::where('code', $data['role_code'])->firstOrFail();
            $user->roles()->sync([$role->id]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Cập nhật người dùng thành công');
    }


    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Xóa người dùng thành công');
    }
    public function showChangePasswordForm()
    {
        return view('user.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'new_password.confirmed' => 'Mật khẩu mới nhập lại không khớp.',
            'new_password.min' => 'Mật khẩu mới phải tối thiểu :min ký tự.',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Mật khẩu hiện tại không đúng.'])->withInput();
        }

        if (Hash::check($request->new_password, $user->password)) {
            return back()->withErrors(['new_password' => 'Mật khẩu mới không được trùng mật khẩu cũ.'])->withInput();
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'Đổi mật khẩu thành công.');
    }
}
