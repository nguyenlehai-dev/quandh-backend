<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Meeting\Models\AttendeeGroup;
use App\Modules\Meeting\Models\MeetingDocumentField;
use App\Modules\Meeting\Models\MeetingDocumentSigner;
use App\Modules\Meeting\Models\MeetingDocumentType;
use App\Modules\Meeting\Models\MeetingIssuingAgency;
use App\Modules\Meeting\Models\MeetingType;
use Illuminate\Database\Seeder;

class MeetingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $organizationId = Organization::query()->value('id');
        $userId = User::query()->value('id');

        $meetingTypes = collect([
            ['name' => 'Họp thường kỳ', 'description' => 'Cuộc họp định kỳ của tổ chức hoặc đơn vị.'],
            ['name' => 'Họp chuyên đề', 'description' => 'Cuộc họp tập trung xử lý một nhóm nội dung chuyên môn.'],
            ['name' => 'Họp giao ban', 'description' => 'Cuộc họp cập nhật tình hình, tiến độ và công việc điều hành.'],
            ['name' => 'Họp lấy ý kiến', 'description' => 'Cuộc họp phục vụ thảo luận và lấy ý kiến biểu quyết.'],
        ])->map(function (array $item) use ($organizationId, $userId) {
            return $this->seedModel(MeetingType::class, $item, $organizationId, $userId);
        });

        $defaultMeetingType = $meetingTypes->first();
        $defaultMeetingTypeId = $defaultMeetingType ? $defaultMeetingType->id : null;

        foreach ([
            ['name' => 'Chủ trì cuộc họp', 'description' => 'Nhóm người điều hành và chịu trách nhiệm chính cuộc họp.'],
            ['name' => 'Thư ký cuộc họp', 'description' => 'Nhóm người ghi nhận biên bản, kết luận và tổng hợp tài liệu.'],
            ['name' => 'Đại biểu tham dự', 'description' => 'Nhóm người tham dự chính thức theo thành phần được mời.'],
            ['name' => 'Khách mời', 'description' => 'Nhóm khách mời tham gia theo nội dung cuộc họp.'],
        ] as $item) {
            $this->seedModel(AttendeeGroup::class, array_merge($item, ['meeting_type_id' => $defaultMeetingTypeId]), $organizationId, $userId);
        }

        foreach ([
            ['name' => 'Tờ trình', 'description' => 'Tài liệu trình bày nội dung cần xem xét hoặc phê duyệt.'],
            ['name' => 'Báo cáo', 'description' => 'Tài liệu báo cáo tình hình, kết quả hoặc phương án xử lý.'],
            ['name' => 'Dự thảo nghị quyết', 'description' => 'Tài liệu dự thảo phục vụ thảo luận và biểu quyết.'],
            ['name' => 'Biên bản cuộc họp', 'description' => 'Tài liệu ghi nhận nội dung, ý kiến và kết quả cuộc họp.'],
        ] as $item) {
            $this->seedModel(MeetingDocumentType::class, array_merge($item, ['meeting_type_id' => $defaultMeetingTypeId]), $organizationId, $userId);
        }

        foreach ([
            ['name' => 'Hành chính', 'description' => 'Tài liệu thuộc nhóm hành chính, tổ chức.'],
            ['name' => 'Tài chính', 'description' => 'Tài liệu thuộc nhóm tài chính, ngân sách.'],
            ['name' => 'Nhân sự', 'description' => 'Tài liệu thuộc nhóm nhân sự, tổ chức bộ máy.'],
            ['name' => 'Kỹ thuật', 'description' => 'Tài liệu thuộc nhóm kỹ thuật, vận hành.'],
        ] as $item) {
            $this->seedModel(MeetingDocumentField::class, $item, $organizationId, $userId);
        }

        foreach ([
            ['name' => 'Chủ tọa', 'position' => 'Chủ trì cuộc họp', 'description' => 'Người ký hoặc xác nhận tài liệu với vai trò chủ tọa.'],
            ['name' => 'Thư ký', 'position' => 'Thư ký cuộc họp', 'description' => 'Người ký hoặc xác nhận biên bản, kết luận cuộc họp.'],
            ['name' => 'Người ban hành', 'position' => 'Đại diện đơn vị ban hành', 'description' => 'Người đại diện cơ quan hoặc đơn vị ban hành tài liệu.'],
        ] as $item) {
            $this->seedModel(MeetingDocumentSigner::class, $item, $organizationId, $userId);
        }

        foreach ([
            ['name' => 'Văn phòng', 'description' => 'Đơn vị đầu mối phát hành hoặc tổng hợp tài liệu cuộc họp.'],
            ['name' => 'Ban tổ chức', 'description' => 'Đơn vị phụ trách thành phần tham dự và công tác tổ chức.'],
            ['name' => 'Phòng tổng hợp', 'description' => 'Đơn vị tổng hợp nội dung, báo cáo và kết luận.'],
        ] as $item) {
            $this->seedModel(MeetingIssuingAgency::class, $item, $organizationId, $userId);
        }
    }

    protected function seedModel(string $modelClass, array $attributes, ?int $organizationId, ?int $userId)
    {
        $identity = [
            'name' => $attributes['name'],
            'organization_id' => $organizationId,
        ];

        if (array_key_exists('meeting_type_id', $attributes)) {
            $identity['meeting_type_id'] = $attributes['meeting_type_id'];
        }

        return $modelClass::query()->updateOrCreate(
            $identity,
            array_merge(
                $attributes,
                [
                'status' => $attributes['status'] ?? 'active',
                'organization_id' => $organizationId,
                'created_by' => $userId,
                'updated_by' => $userId,
                ],
            ),
        );
    }
}
