<?php

namespace App\Http\Controllers\Mission;

use App\Enums\MissionTypeEnum;
use App\Enums\PeriodTypeEnum;
use App\Enums\UnitTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\MissionRequest;
use App\Models\Mission\Mission;
use App\Models\Mission\MissionGroup;
use App\Models\Resolution\Resolution;
use App\Models\Agency\AgencyGroup;
use App\Services\Mission\MissionDashboardCreateService;
use Illuminate\Http\Request;

class MissionController extends Controller
{
    public function index(Resolution $resolution)
    {
        $user = auth()->user();
        $isAdminOrSupervisor = $user->hasRole(['admin', 'supervisor']);

        $groups = MissionGroup::query()
            ->where('resolution_id', $resolution->id)
            ->with([
                'missions' => function ($q) use ($user, $isAdminOrSupervisor) {
                    $q->whereNull('parent_mission_id')->orderBy('id');

                    if (! $isAdminOrSupervisor) {
                        $q->whereHas('missionAgencies', function ($qa) use ($user) {
                            $qa->where('agency_id', $user->agency_id);
                        });
                    }

                    $q->with([
                        'getChildren' => function ($qc) use ($user, $isAdminOrSupervisor) {
                            if (! $isAdminOrSupervisor) {
                                $qc->whereHas('missionAgencies', function ($qa) use ($user) {
                                    $qa->where('agency_id', $user->agency_id);
                                });
                            }
                            $qc->with('getChildren');
                        },
                        'missionAgencies' => function ($qm) use ($user, $isAdminOrSupervisor) {
                            if (! $isAdminOrSupervisor) {
                                $qm->where('agency_id', $user->agency_id);
                            }
                            $qm->with([
                                'agency',
                                'childrenAgency',
                            ]);
                        },
                        'agencies.group',
                    ]);
                },
            ])
            ->orderBy('id')
            ->get();

        return view('mission.index', compact('resolution', 'groups'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $resolution = Resolution::with('reports')
            ->findOrFail($request->resolution_id);

        $selectedPeriods = $resolution->reports
            ->where('unit_type', UnitTypeEnum::MISSION)
            ->pluck('period_type')
            ->unique()
            ->values()
            ->toArray();

        if ($user->hasRole(['admin', 'supervisor'])) {
            $agencies = AgencyGroup::with([
                'agencies' => fn ($q) => $q->whereNull('parent_agency_id')
            ])->orderBy('id')->get();
        } else {
            $agencies = AgencyGroup::with([
                'agencies' => fn ($q) =>
                    $q->where('parent_agency_id', $user->agency_id)
            ])->orderBy('id')->get();
        }

        return view('mission.create', [
            'resolution'      => $resolution,
            'groups'          => MissionGroup::where('resolution_id', $resolution->id)->get(),
            'types'           => MissionTypeEnum::options(),
            'agencyGroups'    => $agencies,
            'periodTypes'     => PeriodTypeEnum::options(),
            'selectedPeriods' => $selectedPeriods,
        ]);
    }

    public function store(MissionRequest $request)
    {
        $user = auth()->user();

        $data = $request->validated();
        unset($data['resolution_id']);

        $editableUntil = now()->addHours(24);

        if ($user->hasRole(['admin', 'supervisor']) && $request->filled('editable_until')) {
            $editableUntil = $request->editable_until;
        }

        $mission = Mission::create(array_merge($data, [
            'created_by'     => $user->id,
            'editable_until' => $editableUntil,
        ]));

        /* ================== PHÂN CÔNG ================== */

        $rows = collect($request->agency_ids ?? [])
            ->map(function ($agencyId) use ($user, $mission) {

                if ($user->hasRole(['admin', 'supervisor'])) {
                    // Admin: agency cấp 1
                    return [
                        'mission_id' => $mission->id,
                        'agency_id'  => $agencyId,
                        'children_agency_id' => null,
                    ];
                }

                // Sub-admin: agency con
                return [
                    'mission_id' => $mission->id,
                    'agency_id'  => $user->agency_id,
                    'children_agency_id' => $agencyId,
                ];
            });

        $mission->missionAgencies()->createMany($rows->toArray());

        /* ================== REPORT PERIODS ================== */

        $selectedPeriods = collect($request->input('period_types', []))
            ->sort()
            ->values();

        $resolutionPeriods = $mission->group
            ->resolution
            ->reports
            ->where('unit_type', UnitTypeEnum::MISSION)
            ->pluck('period_type')
            ->unique()
            ->sort()
            ->values();

        if (
            $selectedPeriods->diff($resolutionPeriods)->isNotEmpty() ||
            $resolutionPeriods->diff($selectedPeriods)->isNotEmpty()
        ) {
            $mission->reportPeriods()->createMany(
                $selectedPeriods->map(fn ($p) => [
                    'period_type' => $p
                ])->toArray()
            );
        }

        app(MissionDashboardCreateService::class)->execute($mission);

        return redirect()
            ->route('missions.index', $mission->group->resolution_id)
            ->with('success', 'Thêm nhiệm vụ thành công');
    }

    public function edit(Mission $mission)
    {
        $user = auth()->user();
        $resolution = $mission->group->resolution;
        $isAdminOrSupervisor = $user->hasRole(['admin', 'supervisor']);
        $isCreatedByAdmin = $mission->created_by != $user->id;

        if (! $isAdminOrSupervisor) {

            $isCreator = $mission->created_by == $user->id;

            $isAssigned = $mission->missionAgencies()
                ->where('agency_id', $user->agency_id)
                ->exists();

            if (! $isCreator && ! $isAssigned) {
                return redirect()->route(
                    'missions.index',
                    $mission->group->resolution_id
                );
            }

            if (! $mission->canEdit()) {
                return redirect()->route(
                    'missions.index',
                    $mission->group->resolution_id
                );
            }
        }

        /* ===== KỲ BÁO CÁO ===== */
        $periodTypes = PeriodTypeEnum::options();

        if ($mission->reportPeriods()->exists()) {
            $selectedPeriods = $mission->reportPeriods
                ->pluck('period_type')
                ->toArray();
        } else {
            $selectedPeriods = $resolution->reports
                ->where('unit_type', UnitTypeEnum::MISSION)
                ->pluck('period_type')
                ->unique()
                ->toArray();
        }

        if ($isAdminOrSupervisor) {
            $agencyGroups = AgencyGroup::with([
                'agencies' => fn ($q) => $q->whereNull('parent_agency_id')
            ])->orderBy('id')->get();
        } else {
            $agencyGroups = AgencyGroup::with([
                'agencies' => fn ($q) =>
                    $q->where('parent_agency_id', $user->agency_id)
            ])->orderBy('id')->get();
        }

        /* ===== AGENCY ĐÃ GÁN ===== */
        $assignedAgencyIds = $mission->missionAgencies
            ->map(fn ($ma) =>
                $isAdminOrSupervisor
                    ? $ma->agency_id
                    : $ma->children_agency_id
            )
            ->filter()
            ->values()
            ->toArray();

        return view('mission.edit', [
            'mission'           => $mission,
            'resolution'        => $resolution,
            'groups'            => MissionGroup::where('resolution_id', $resolution->id)->get(),
            'types'             => MissionTypeEnum::options(),
            'agencyGroups'      => $agencyGroups,
            'assignedAgencies'  => $assignedAgencyIds,
            'periodTypes'       => $periodTypes,
            'selectedPeriods'   => $selectedPeriods,
            'isAdminOrSupervisor' => $isAdminOrSupervisor,
            'isCreatedByAdmin'  => $isCreatedByAdmin,
        ]);
    }

    public function update(MissionRequest $request, Mission $mission)
    {
        $user = auth()->user();

        $data = $request->validated();
        unset($data['resolution_id']);

        $isAdminOrSupervisor = $user->hasRole(['admin', 'supervisor']);

        if ($isAdminOrSupervisor) {
            $mission->update($data);
        } else {
            if (! $mission->canEdit()) {
                return redirect()->route(
                    'missions.index',
                    $mission->group->resolution_id
                );
            }
            if ($mission->created_by == $user->id) {
                $mission->update($data);
            } else {
            }
        }
        /* ===== PHÂN CÔNG ===== */

        if ($user->hasRole(['admin', 'supervisor'])) {
            $newAgencyIds = collect($request->agency_ids ?? [])
                ->map(fn ($id) => (int) $id);

            $existing = $mission->missionAgencies()->get();

            /* ===== XÓA AGENCY CHA KHÔNG CÒN ĐƯỢC CHỌN ===== */
            $existing
                ->whereNotIn('agency_id', $newAgencyIds)
                ->each(fn ($ma) => $ma->delete());

            /* ===== THÊM AGENCY CHA MỚI (CHƯA TỒN TẠI) ===== */
            $existingAgencyIds = $existing->pluck('agency_id');

            $toInsert = $newAgencyIds
                ->diff($existingAgencyIds)
                ->map(fn ($agencyId) => [
                    'mission_id'         => $mission->id,
                    'agency_id'          => $agencyId,
                    'children_agency_id' => null,
                ]);

            if ($toInsert->isNotEmpty()) {
                $mission->missionAgencies()->createMany($toInsert->toArray());
            }
        } else {
            $missionAgency = $mission->missionAgencies()
                ->where('agency_id', $user->agency_id)
                ->firstOrFail();

            $missionAgency->update([
                'children_agency_id' => $request->agency_ids[0] ?? null,
            ]);
        }

        /* ===== KỲ BÁO CÁO ===== */
        $selectedPeriods = collect($request->input('period_types', []))
            ->sort()
            ->values();

        $resolutionPeriods = $mission->group
            ->resolution
            ->reports
            ->where('unit_type', UnitTypeEnum::MISSION)
            ->pluck('period_type')
            ->unique()
            ->sort()
            ->values();

        $mission->reportPeriods()->delete();

        if (
            $selectedPeriods->diff($resolutionPeriods)->isNotEmpty() ||
            $resolutionPeriods->diff($selectedPeriods)->isNotEmpty()
        ) {
            $mission->reportPeriods()->createMany(
                $selectedPeriods->map(fn ($p) => [
                    'period_type' => $p,
                ])->toArray()
            );
        }

        app(MissionDashboardCreateService::class)->execute($mission);

        return redirect()
            ->route('missions.index', $mission->group->resolution_id)
            ->with('success', 'Cập nhật nhiệm vụ thành công');
    }

    public function destroy(Mission $mission)
    {
        $resolutionId = $mission->group->resolution_id;
        $user = auth()->user();

        if (! $user->hasRole(['admin', 'supervisor'])) {
            abort(403, 'This action is unauthorized!');
        }
        
        $mission->delete();

        return redirect()
            ->route('missions.index', $resolutionId)
            ->with('success', 'Xóa nhiệm vụ thành công');
    }

    public function getParentsByGroup(Request $request)
    {
        $user = auth()->user();
        $groupId = $request->mission_group_id;

        $query = Mission::where('mission_group_id', $groupId)
            ->whereNull('parent_mission_id');

        // Sub_admin: chỉ lấy nhiệm vụ agency mình phụ trách
        if (! $user->hasRole(['admin', 'supervisor'])) {
            $query->whereHas('agencies', function ($q) use ($user) {
                $q->where('agency_id', $user->agency_id);
            });
        }

        return $query
            ->orderBy('mission_name')
            ->get(['id', 'mission_name']);
    }
}
