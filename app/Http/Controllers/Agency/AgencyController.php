<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\AgencyRequest;
use App\Models\Agency\Agency;
use App\Models\Agency\AgencyGroup;
use Illuminate\Support\Facades\Schema;

class AgencyController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole(['admin', 'supervisor'])) {
            $agencies = Agency::with('group', 'parent')
                ->orderBy('id')
                ->get();
        } else {
            // Sub-admin: chỉ xem agency con của mình
            $agencies = Agency::with('group', 'parent')
                ->where('parent_agency_id', $user->agency_id)
                ->orderBy('id')
                ->get();
        }

        return redirect()->route('agencies.manage');
    }

    public function create()
    {
        $user = auth()->user();

        if ($user->hasRole(['admin', 'supervisor'])) {
            $parents = Agency::whereNull('parent_agency_id')->get();
            $groups = AgencyGroup::all();
        } else {
            $myAgency = Agency::query()->findOrFail($user->agency_id);
            $parents = Agency::query()->where('id', $myAgency->id)->get();
            $groups = AgencyGroup::query()->where('id', $myAgency->agency_group_id)->get();
        }

        return view('agency.create', [
            'groups' => $groups,
            'parents' => $parents,
        ]);
    }

    public function store(AgencyRequest $request)
    {
        $user = auth()->user();
        $data = $request->validated();

        if (!$user->hasRole(['admin', 'supervisor'])) {
            $myAgency = Agency::query()->findOrFail($user->agency_id);
            $data['parent_agency_id'] = $myAgency->id;
            $data['agency_group_id'] = $myAgency->agency_group_id;
        }

        Agency::create($data);

        if ((int) $request->input('modal', 0) === 1) {
            return view('agency.modal_done', ['message' => 'Tạo cơ quan thành công']);
        }

        return redirect()->route('agencies.manage');
    }

    public function edit(Agency $agency)
    {
        $user = auth()->user();

        if (!$user->hasRole(['admin', 'supervisor'])) {
            abort_if(
                $agency->parent_agency_id !== $user->agency_id,
                403
            );
        }

        if ($user->hasRole(['admin', 'supervisor'])) {
            $parents = Agency::where('id', '!=', $agency->id)
                ->whereNull('parent_agency_id')
                ->get();
        } else {
            // Sub-admin: parent cố định
            $parents = Agency::where('id', $user->agency_id)->get();
        }

        return view('agency.edit', [
            'agency' => $agency,
            'groups' => AgencyGroup::all(),
            'parents' => $parents,
        ]);
    }

    public function update(AgencyRequest $request, Agency $agency)
    {
        $user = auth()->user();
        $data = $request->validated();

        if (!$user->hasRole(['admin', 'supervisor'])) {
            abort_if(
                $agency->parent_agency_id !== $user->agency_id,
                403
            );
            $data['parent_agency_id'] = $user->agency_id;
        }

        $agency->update($data);

        if ((int) $request->input('modal', 0) === 1) {
            return view('agency.modal_done', ['message' => 'Cập nhật thành công']);
        }

        return redirect()->route('agencies.manage');
    }

    public function destroy(Agency $agency)
    {
        $user = auth()->user();

        if (! $user->hasRole(['admin', 'supervisor'])) {
            abort(403, 'This action is unauthorized!');
        }

        $agency->delete();

        return redirect()->route('agencies.manage');
    }
    
    public function byParent($parentId)
    {
        return Agency::where('parent_agency_id', $parentId)
            ->orderBy('agency_name')
            ->get(['id', 'agency_name']);
    }

    public function byGroup($groupId)
    {
        return Agency::query()
            ->where('agency_group_id', $groupId)
            ->orderBy('agency_name')
            ->whereNull('parent_agency_id')
            ->get(['id', 'agency_name']);
    }
    /**
     * Single combined page: Agency Group -> Agency tree
     */
    /**
     * Single combined page: Agency Group -> Agency tree
     * Scope:
     * - admin/supervisor: all agencies
     * - other roles: only the user's agency + all descendants (agencies "related" to the agency they manage)
     */

    public function manage()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'supervisor']);

        $groups = AgencyGroup::query()->orderBy('group_name')->get();

        // Detect common column names to avoid schema mismatch
        $agencyTable = (new Agency())->getTable();
        $cols = Schema::getColumnListing($agencyTable);

        $pick = function (array $cands) use ($cols) {
            foreach ($cands as $c) {
                if (in_array($c, $cols, true)) {
                    return $c;
                }
            }
            return null;
        };

        $groupKey = $pick(['agency_group_id', 'group_id', 'agency_group']);
        $parentKey = $pick(['parent_id', 'parent_agency_id', 'agency_parent_id']) ?: 'parent_id';
        $nameKey = $pick(['agency_name', 'name', 'title']) ?: 'name';
        $codeKey = $pick(['agency_code', 'code', 'short_code']);

        $allAgencies = Agency::query()
            ->when($groupKey, fn($q) => $q->orderBy($groupKey))
            ->orderBy($parentKey)
            ->orderBy($nameKey)
            ->get();

        // ===== Scope theo user =====
        // Admin/Supervisor: thấy tất cả
        // Các role khác: chỉ thấy agency của mình + toàn bộ agency con (nhiều cấp)
        if (!$isAdmin) {
            $rootId = (int) ($user->agency_id ?? 0);

            if ($rootId <= 0) {
                $scopedAgencies = collect();
            } else {
                // Build parent => children map
                $byParent = [];
                foreach ($allAgencies as $a) {
                    $pid = (int) ($a->{$parentKey} ?? 0);
                    $byParent[$pid][] = $a;
                }

                // DFS để lấy toàn bộ descendants
                $keep = [];
                $stack = [$rootId];
                while (!empty($stack)) {
                    $id = (int) array_pop($stack);
                    if ($id <= 0 || isset($keep[$id])) {
                        continue;
                    }
                    $keep[$id] = true;

                    foreach (($byParent[$id] ?? []) as $child) {
                        $stack[] = (int) $child->id;
                    }
                }

                $scopedAgencies = $allAgencies
                    ->filter(fn($a) => isset($keep[(int) $a->id]))
                    ->values();
            }
        } else {
            $scopedAgencies = $allAgencies;
        }

        // ===== Group -> Agency tree =====
        $agenciesByGroup = [];
        foreach ($scopedAgencies as $a) {
            $gid = $groupKey ? (int) ($a->{$groupKey} ?? 0) : 0;
            $agenciesByGroup[$gid][] = $a;
        }

        // Build tree, và nếu parent nằm ngoài scope -> đưa node đó lên root để không bị "mất" khỏi cây
        $buildTree = function (array $items) use ($parentKey) {
            $idSet = [];
            foreach ($items as $it) {
                $idSet[(int) $it->id] = true;
            }

            $byParent = [];
            foreach ($items as $item) {
                $pid = (int) ($item->{$parentKey} ?? 0);

                // Nếu parent không tồn tại trong tập items -> promote lên root (pid=0)
                if ($pid !== 0 && !isset($idSet[$pid])) {
                    $pid = 0;
                }

                $byParent[$pid][] = $item;
            }

            $walk = function ($parentId) use (&$walk, $byParent) {
                $children = $byParent[$parentId] ?? [];
                $out = [];
                foreach ($children as $c) {
                    $out[] = [
                        'model' => $c,
                        'children' => $walk((int) $c->id),
                    ];
                }
                return $out;
            };

            return $walk(0);
        };

        $groupTrees = [];
        foreach ($groups as $g) {
            $groupTrees[(int) $g->id] = $buildTree($agenciesByGroup[(int) $g->id] ?? []);
        }

        return view('agency.combined', [
            'groups' => $groups,
            'groupTrees' => $groupTrees,
            'meta' => compact('nameKey', 'codeKey'),
        ]);
    }
}
