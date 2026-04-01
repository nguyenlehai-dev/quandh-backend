<?php

namespace App\Modules\Core\Resources;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

class UserCollection extends ResourceCollection
{
    public $collects = UserResource::class;

    public function toArray(Request $request): array
    {
        $this->hydrateAssignments();

        return parent::toArray($request);
    }

    protected function hydrateAssignments(): void
    {
        $userIds = $this->collection
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $rolePivotKey = $columnNames['role_pivot_key'] ?? 'role_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'organization_id';

        $rows = DB::table($modelHasRolesTable)
            ->where('model_type', User::class)
            ->whereIn($modelMorphKey, $userIds)
            ->select([
                $modelMorphKey.' as user_id',
                $teamForeignKey.' as organization_id',
                $rolePivotKey.' as role_id',
            ])
            ->get();

        if ($rows->isEmpty()) {
            foreach ($this->collection as $user) {
                $user->setAttribute('prefetched_assignments', []);
            }

            return;
        }

        $roleIds = $rows->pluck('role_id')->unique()->values();
        $organizationIds = $rows->pluck('organization_id')->unique()->values();

        $roles = Role::whereIn('id', $roleIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $organizations = Organization::whereIn('id', $organizationIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $assignmentsByUserId = $rows
            ->groupBy('user_id')
            ->map(function ($userRows) use ($roles, $organizations) {
                return $userRows
                    ->groupBy('role_id')
                    ->map(function ($items, $roleId) use ($roles, $organizations) {
                        $role = $roles->get((int) $roleId);

                        return [
                            'role_id' => (int) $roleId,
                            'role_name' => $role?->name,
                            'organization_ids' => $items
                                ->pluck('organization_id')
                                ->map(fn ($id) => (int) $id)
                                ->unique()
                                ->values()
                                ->all(),
                            'organizations' => $items
                                ->pluck('organization_id')
                                ->map(fn ($id) => (int) $id)
                                ->unique()
                                ->values()
                                ->map(fn ($id) => [
                                    'id' => $id,
                                    'name' => $organizations->get($id)?->name,
                                ])
                                ->all(),
                        ];
                    })
                    ->values()
                    ->all();
            });

        foreach ($this->collection as $user) {
            $user->setAttribute('prefetched_assignments', $assignmentsByUserId->get($user->id, []));
        }
    }
}
