<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\AttendeeGroup;
use App\Modules\Meeting\Models\MeetingDocumentField;
use App\Modules\Meeting\Models\MeetingDocumentSigner;
use App\Modules\Meeting\Models\MeetingDocumentType;
use App\Modules\Meeting\Models\MeetingIssuingAgency;
use App\Modules\Meeting\Models\MeetingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogService
{
    protected array $models = [
        'meeting-types' => MeetingType::class,
        'attendee-groups' => AttendeeGroup::class,
        'meeting-document-types' => MeetingDocumentType::class,
        'meeting-document-fields' => MeetingDocumentField::class,
        'meeting-document-signers' => MeetingDocumentSigner::class,
        'meeting-issuing-agencies' => MeetingIssuingAgency::class,
    ];

    protected array $labels = [
        'meeting-types' => 'loại cuộc họp',
        'attendee-groups' => 'nhóm thành phần tham dự',
        'meeting-document-types' => 'loại tài liệu cuộc họp',
        'meeting-document-fields' => 'lĩnh vực tài liệu cuộc họp',
        'meeting-document-signers' => 'người ký tài liệu cuộc họp',
        'meeting-issuing-agencies' => 'cơ quan ban hành tài liệu cuộc họp',
    ];

    public function label(string $resource): string
    {
        return $this->labels[$resource] ?? 'danh mục';
    }

    public function publicList(string $resource, array $filters)
    {
        return $this->query($resource, $filters)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function publicOptions(string $resource, array $filters)
    {
        return $this->query($resource, $filters)
            ->where('status', 'active')
            ->select(['id', 'name', 'description'])
            ->orderBy('name')
            ->get();
    }

    public function stats(string $resource, array $filters): array
    {
        $query = $this->query($resource, $filters);

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'inactive' => (clone $query)->where('status', 'inactive')->count(),
        ];
    }

    public function index(string $resource, array $filters, int $limit)
    {
        return $this->query($resource, $filters)
            ->with($this->relations($resource))
            ->orderBy($this->sortBy($filters['sort_by'] ?? null), ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc')
            ->paginate($limit);
    }

    public function show(string $resource, int $id): Model
    {
        return $this->find($resource, $id)->load($this->relations($resource));
    }

    public function store(string $resource, array $validated, array $members = []): Model
    {
        $validated['status'] ??= 'active';

        return DB::transaction(function () use ($resource, $validated, $members) {
            $model = $this->modelClass($resource)::query()->create($validated);
            $this->syncMembers($resource, $model, $members);

            return $model->load($this->relations($resource));
        });
    }

    public function update(string $resource, int $id, array $validated, ?array $members = null): Model
    {
        return DB::transaction(function () use ($resource, $id, $validated, $members) {
            $model = $this->find($resource, $id);
            $validated['status'] ??= $model->status;
            $model->update($validated);
            if ($members !== null) {
                $this->syncMembers($resource, $model, $members);
            }

            return $model->load($this->relations($resource));
        });
    }

    public function destroy(string $resource, int $id): void
    {
        $this->find($resource, $id)->delete();
    }

    public function bulkDestroy(string $resource, array $ids): void
    {
        $this->query($resource)->whereIn('id', $ids)->delete();
    }

    public function bulkUpdateStatus(string $resource, array $ids, string $status): void
    {
        $this->query($resource)->whereIn('id', $ids)->update(['status' => $status]);
    }

    public function changeStatus(string $resource, int $id, string $status): Model
    {
        $model = $this->find($resource, $id);
        $model->update(['status' => $status]);

        return $model->fresh()->load($this->relations($resource));
    }

    public function relations(string $resource): array
    {
        return match ($resource) {
            'attendee-groups' => ['meetingType', 'members.user', 'creator', 'editor'],
            'meeting-document-types' => ['meetingType', 'creator', 'editor'],
            default => ['creator', 'editor'],
        };
    }

    protected function query(string $resource, array $filters = [])
    {
        return $this->modelClass($resource)::query()
            ->forCurrentOrganization()
            ->searchByName($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['meeting_type_id'] ?? null, fn ($q, $id) => $q->where('meeting_type_id', $id));
    }

    protected function find(string $resource, int $id): Model
    {
        return $this->query($resource)->findOrFail($id);
    }

    protected function modelClass(string $resource): string
    {
        abort_unless(isset($this->models[$resource]), 404, 'Không tìm thấy resource Meeting.');

        return $this->models[$resource];
    }

    protected function sortBy(?string $sortBy): string
    {
        return in_array($sortBy, ['id', 'name', 'status', 'created_at', 'updated_at'], true) ? $sortBy : 'created_at';
    }

    protected function syncMembers(string $resource, Model $model, array $members): void
    {
        if ($resource !== 'attendee-groups') {
            return;
        }

        $model->members()->delete();

        foreach ($members as $member) {
            $model->members()->create([
                'user_id' => $member['user_id'],
                'position' => $member['position'] ?? null,
            ]);
        }
    }
}
