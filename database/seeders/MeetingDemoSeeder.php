<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\User;
use App\Modules\Meeting\Models\AttendeeGroup;
use App\Modules\Meeting\Models\AttendeeGroupMember;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingAgenda;
use App\Modules\Meeting\Models\MeetingConclusion;
use App\Modules\Meeting\Models\MeetingDocument;
use App\Modules\Meeting\Models\MeetingDocumentField;
use App\Modules\Meeting\Models\MeetingDocumentSigner;
use App\Modules\Meeting\Models\MeetingDocumentType;
use App\Modules\Meeting\Models\MeetingIssuingAgency;
use App\Modules\Meeting\Models\MeetingParticipant;
use App\Modules\Meeting\Models\MeetingPersonalNote;
use App\Modules\Meeting\Models\MeetingReminder;
use App\Modules\Meeting\Models\MeetingSpeechRequest;
use App\Modules\Meeting\Models\MeetingType;
use App\Modules\Meeting\Models\MeetingVoteResult;
use App\Modules\Meeting\Models\MeetingVoting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MeetingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MeetingCatalogSeeder::class);

        $organizationId = Organization::query()->value('id');
        $users = User::query()->orderBy('id')->limit(6)->get();
        $admin = $users->first();

        if (! $admin) {
            return;
        }

        $meetingTypes = MeetingType::query()
            ->where('organization_id', $organizationId)
            ->orderBy('id')
            ->get();

        $documentType = MeetingDocumentType::query()->where('organization_id', $organizationId)->orderBy('id')->first();
        $documentField = MeetingDocumentField::query()->where('organization_id', $organizationId)->orderBy('id')->first();
        $documentSigner = MeetingDocumentSigner::query()->where('organization_id', $organizationId)->orderBy('id')->first();
        $issuingAgency = MeetingIssuingAgency::query()->where('organization_id', $organizationId)->orderBy('id')->first();

        $this->seedGroupMembers($users);

        $primaryMeeting = $this->upsertMeeting(
            organizationId: $organizationId,
            adminId: $admin->id,
            meetingTypeId: $meetingTypes->first()?->id,
            code: 'MTG-DEMO-001',
            title: 'Họp giao ban điều hành tuần',
            description: 'Cuộc họp mẫu dùng để kiểm thử đầy đủ các nghiệp vụ Họp không giấy.',
            location: 'Phòng họp trực tuyến',
            startAt: now()->addDay()->setTime(9, 0),
            endAt: now()->addDay()->setTime(11, 0),
            status: 'active',
        );

        $this->seedPrimaryMeetingData(
            meeting: $primaryMeeting,
            users: $users,
            adminId: $admin->id,
            documentTypeId: $documentType?->id,
            documentFieldId: $documentField?->id,
            documentSignerId: $documentSigner?->id,
            issuingAgencyId: $issuingAgency?->id,
        );

        $this->seedAdditionalMeetings(
            organizationId: $organizationId,
            adminId: $admin->id,
            users: $users,
            meetingTypes: $meetingTypes,
            documentTypeId: $documentType?->id,
            documentFieldId: $documentField?->id,
            documentSignerId: $documentSigner?->id,
            issuingAgencyId: $issuingAgency?->id,
        );
    }

    protected function seedGroupMembers(Collection $users): void
    {
        $groups = AttendeeGroup::query()->orderBy('id')->get();

        foreach ($groups as $index => $group) {
            $user = $users->get($index % max($users->count(), 1));

            if (! $user) {
                continue;
            }

            AttendeeGroupMember::query()->updateOrCreate(
                ['attendee_group_id' => $group->id, 'user_id' => $user->id],
                ['position' => $group->name]
            );
        }
    }

    protected function upsertMeeting(
        ?int $organizationId,
        int $adminId,
        ?int $meetingTypeId,
        string $code,
        string $title,
        string $description,
        string $location,
        $startAt,
        $endAt,
        string $status,
    ): Meeting {
        return Meeting::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'code' => $code],
            [
                'meeting_type_id' => $meetingTypeId,
                'title' => $title,
                'description' => $description,
                'location' => $location,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => $status,
                'qr_token' => optional(Meeting::query()->where('organization_id', $organizationId)->where('code', $code)->first())->qr_token ?: (string) Str::uuid(),
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );
    }

    protected function seedPrimaryMeetingData(
        Meeting $meeting,
        Collection $users,
        int $adminId,
        ?int $documentTypeId,
        ?int $documentFieldId,
        ?int $documentSignerId,
        ?int $issuingAgencyId,
    ): void {
        $secretaryUser = $users->get(1) ?: $users->first();
        $delegateUser = $users->get(2) ?: $users->first();
        $guestUser = $users->get(3) ?: $users->first();

        $participants = [
            [
                'user_id' => $users->first()?->id,
                'role' => 'chair',
                'position' => 'Chủ trì',
                'status' => 'present',
                'checkin_at' => now()->subMinutes(15),
                'absence_reason' => null,
                'delegated_to_id' => null,
                'sort_order' => 1,
            ],
            [
                'user_id' => $secretaryUser?->id,
                'role' => 'secretary',
                'position' => 'Thư ký',
                'status' => 'pending',
                'checkin_at' => null,
                'absence_reason' => null,
                'delegated_to_id' => null,
                'sort_order' => 2,
            ],
            [
                'user_id' => $delegateUser?->id,
                'role' => 'delegate',
                'position' => 'Đại biểu chính',
                'status' => 'delegated',
                'checkin_at' => null,
                'absence_reason' => null,
                'delegated_to_id' => $guestUser?->id,
                'sort_order' => 3,
            ],
            [
                'user_id' => $guestUser?->id,
                'role' => 'guest',
                'position' => 'Khách mời',
                'status' => 'absent',
                'checkin_at' => null,
                'absence_reason' => 'Đang công tác ngoài đơn vị.',
                'delegated_to_id' => null,
                'sort_order' => 4,
            ],
        ];

        foreach ($participants as $participant) {
            if (! $participant['user_id']) {
                continue;
            }

            MeetingParticipant::query()->updateOrCreate(
                ['meeting_id' => $meeting->id, 'user_id' => $participant['user_id']],
                array_merge($participant, [
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ])
            );
        }

        $openingAgenda = MeetingAgenda::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'title' => 'Khai mạc và thông qua chương trình'],
            [
                'description' => 'Chủ trì nêu mục tiêu, phạm vi và chương trình họp.',
                'sort_order' => 1,
                'duration_minutes' => 20,
                'presenter_id' => $users->first()?->id,
                'status' => 'completed',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $discussionAgenda = MeetingAgenda::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'title' => 'Thảo luận và biểu quyết nội dung trọng tâm'],
            [
                'description' => 'Đại biểu thảo luận tài liệu, kết luận và biểu quyết phương án triển khai.',
                'sort_order' => 2,
                'duration_minutes' => 80,
                'presenter_id' => $secretaryUser?->id ?: $adminId,
                'status' => 'in_progress',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $followupAgenda = MeetingAgenda::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'title' => 'Theo dõi đầu việc sau cuộc họp'],
            [
                'description' => 'Xác nhận hạn xử lý, đầu mối theo dõi và phương án báo cáo.',
                'sort_order' => 3,
                'duration_minutes' => 30,
                'presenter_id' => $delegateUser?->id,
                'status' => 'pending',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $meeting->update(['active_agenda_id' => $discussionAgenda->id]);

        $report = MeetingDocument::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'document_number' => 'BC-DEMO-001'],
            [
                'agenda_id' => $discussionAgenda->id,
                'document_type_id' => $documentTypeId,
                'document_field_id' => $documentFieldId,
                'issuing_agency_id' => $issuingAgencyId,
                'document_signer_id' => $documentSignerId,
                'title' => 'Báo cáo tình hình triển khai tuần',
                'description' => 'Tài liệu mẫu phục vụ thảo luận trong cuộc họp.',
                'issued_at' => now()->toDateString(),
                'status' => 'active',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        MeetingDocument::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'document_number' => 'DT-DEMO-001'],
            [
                'agenda_id' => $discussionAgenda->id,
                'document_type_id' => $documentTypeId,
                'document_field_id' => $documentFieldId,
                'issuing_agency_id' => $issuingAgencyId,
                'document_signer_id' => $documentSignerId,
                'title' => 'Dự thảo kết luận cuộc họp',
                'description' => 'Dự thảo mẫu để đại biểu góp ý trước khi chốt kết luận.',
                'issued_at' => now()->toDateString(),
                'status' => 'draft',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        MeetingDocument::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'document_number' => 'TL-DEMO-001'],
            [
                'agenda_id' => $followupAgenda->id,
                'document_type_id' => $documentTypeId,
                'document_field_id' => $documentFieldId,
                'issuing_agency_id' => $issuingAgencyId,
                'document_signer_id' => $documentSignerId,
                'title' => 'Tài liệu lưu trữ kỳ trước',
                'description' => 'Tài liệu được lưu để đối chiếu, không còn hiệu lực thao tác chính.',
                'issued_at' => now()->subDays(7)->toDateString(),
                'status' => 'archived',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        MeetingConclusion::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'title' => 'Kết luận triển khai nhiệm vụ tuần'],
            [
                'agenda_id' => $discussionAgenda->id,
                'content' => 'Các đơn vị cập nhật tiến độ trước 16:00 thứ Sáu và báo cáo nội dung phát sinh về văn phòng tổng hợp.',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $openVoting = MeetingVoting::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'title' => 'Biểu quyết thông qua kế hoạch tuần'],
            [
                'agenda_id' => $discussionAgenda->id,
                'description' => 'Biểu quyết phương án triển khai theo tài liệu đã trình bày.',
                'type' => 'public',
                'status' => 'open',
                'options' => ['agree', 'disagree', 'other'],
                'opened_at' => now()->subMinutes(30),
                'closed_at' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        $closedVoting = MeetingVoting::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'title' => 'Biểu quyết chốt hạn báo cáo'],
            [
                'agenda_id' => $followupAgenda->id,
                'description' => 'Xác nhận hạn cuối báo cáo tổng hợp.',
                'type' => 'anonymous',
                'status' => 'closed',
                'options' => ['agree', 'disagree'],
                'opened_at' => now()->subDay(),
                'closed_at' => now()->subHours(20),
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        MeetingVoting::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'title' => 'Biểu quyết bổ sung nguồn lực'],
            [
                'agenda_id' => $followupAgenda->id,
                'description' => 'Biểu quyết dự kiến mở ở phiên họp sau.',
                'type' => 'public',
                'status' => 'pending',
                'options' => ['agree', 'disagree'],
                'opened_at' => null,
                'closed_at' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        foreach ($users->take(3) as $index => $user) {
            MeetingVoteResult::query()->updateOrCreate(
                ['voting_id' => $openVoting->id, 'user_id' => $user->id],
                [
                    'option' => $index === 1 ? 'disagree' : 'agree',
                    'note' => $index === 1 ? 'Cần cân đối thêm nguồn lực trước khi triển khai.' : 'Đồng ý với phương án trình bày.',
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );
        }

        MeetingVoteResult::query()->updateOrCreate(
            ['voting_id' => $closedVoting->id, 'user_id' => $users->first()->id],
            [
                'option' => 'agree',
                'note' => 'Đã chốt trong cuộc họp trước.',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        MeetingSpeechRequest::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $delegateUser?->id ?: $adminId, 'content' => 'Đề xuất bổ sung mốc kiểm tra giữa tuần.'],
            [
                'agenda_id' => $discussionAgenda->id,
                'status' => 'approved',
                'review_note' => 'Đã duyệt phát biểu trong phần thảo luận.',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        MeetingSpeechRequest::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $guestUser?->id ?: $adminId, 'content' => 'Đề nghị rà soát lại phụ lục tài chính.'],
            [
                'agenda_id' => $discussionAgenda->id,
                'status' => 'pending',
                'review_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        MeetingSpeechRequest::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $secretaryUser?->id ?: $adminId, 'content' => 'Kiến nghị bỏ mục chi chưa có căn cứ pháp lý.'],
            [
                'agenda_id' => $discussionAgenda->id,
                'status' => 'rejected',
                'review_note' => 'Nội dung này đã được chuyển sang phiên họp chuyên đề.',
                'reviewed_by' => $adminId,
                'reviewed_at' => now()->subMinutes(10),
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        MeetingPersonalNote::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'document_id' => $report->id, 'user_id' => $adminId],
            [
                'content' => 'Theo dõi phần báo cáo tiến độ và các đầu việc cần chốt sau cuộc họp.',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        MeetingReminder::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $adminId, 'title' => 'Chuẩn bị tài liệu họp'],
            [
                'content' => 'Kiểm tra tài liệu, danh sách người dự và trạng thái biểu quyết trước giờ họp.',
                'remind_at' => now()->addDay()->setTime(8, 30),
                'status' => 'pending',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        MeetingReminder::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $secretaryUser?->id ?: $adminId, 'title' => 'Gửi kết luận sau họp'],
            [
                'content' => 'Phát hành kết luận cho các đơn vị liên quan.',
                'remind_at' => now()->addDay()->setTime(13, 0),
                'status' => 'sent',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );

        MeetingReminder::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $delegateUser?->id ?: $adminId, 'title' => 'Nhắc bổ sung phụ lục'],
            [
                'content' => 'Đã hủy do tài liệu đã được thay thế.',
                'remind_at' => now()->subHours(6),
                'status' => 'cancelled',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]
        );
    }

    protected function seedAdditionalMeetings(
        ?int $organizationId,
        int $adminId,
        Collection $users,
        Collection $meetingTypes,
        ?int $documentTypeId,
        ?int $documentFieldId,
        ?int $documentSignerId,
        ?int $issuingAgencyId,
    ): void {
        $definitions = [
            [
                'code' => 'MTG-DEMO-002',
                'title' => 'Họp chuẩn bị hồ sơ pháp lý',
                'description' => 'Bản nháp phục vụ rà soát hồ sơ trước khi trình ký.',
                'location' => 'Phòng pháp chế',
                'status' => 'draft',
                'start_at' => now()->addDays(2)->setTime(14, 0),
                'end_at' => now()->addDays(2)->setTime(15, 30),
                'meeting_type_id' => $meetingTypes->get(1)?->id,
            ],
            [
                'code' => 'MTG-DEMO-003',
                'title' => 'Họp triển khai kế hoạch tháng',
                'description' => 'Cuộc họp đang diễn ra, phục vụ kiểm thử lọc theo trạng thái.',
                'location' => 'Phòng họp 2',
                'status' => 'in_progress',
                'start_at' => now()->subMinutes(30),
                'end_at' => now()->addMinutes(90),
                'meeting_type_id' => $meetingTypes->get(2)?->id,
            ],
            [
                'code' => 'MTG-DEMO-004',
                'title' => 'Họp tổng kết quý',
                'description' => 'Cuộc họp đã hoàn thành để test thống kê và chi tiết dữ liệu đóng.',
                'location' => 'Hội trường lớn',
                'status' => 'completed',
                'start_at' => now()->subDays(2)->setTime(8, 30),
                'end_at' => now()->subDays(2)->setTime(11, 30),
                'meeting_type_id' => $meetingTypes->first()?->id,
            ],
            [
                'code' => 'MTG-DEMO-005',
                'title' => 'Họp xử lý tình huống phát sinh',
                'description' => 'Cuộc họp đã hủy để test kịch bản hủy cuộc họp.',
                'location' => 'Phòng điều hành',
                'status' => 'cancelled',
                'start_at' => now()->addDays(3)->setTime(10, 0),
                'end_at' => now()->addDays(3)->setTime(11, 0),
                'meeting_type_id' => $meetingTypes->get(3)?->id,
            ],
        ];

        foreach ($definitions as $index => $definition) {
            $meeting = $this->upsertMeeting(
                organizationId: $organizationId,
                adminId: $adminId,
                meetingTypeId: $definition['meeting_type_id'],
                code: $definition['code'],
                title: $definition['title'],
                description: $definition['description'],
                location: $definition['location'],
                startAt: $definition['start_at'],
                endAt: $definition['end_at'],
                status: $definition['status'],
            );

            $presenterId = $users->get($index % max($users->count(), 1))?->id ?: $adminId;

            $agenda = MeetingAgenda::query()->updateOrCreate(
                ['meeting_id' => $meeting->id, 'title' => 'Nội dung chính '.$definition['code']],
                [
                    'description' => 'Agenda dùng để kiểm thử cuộc họp '.$definition['code'],
                    'sort_order' => 1,
                    'duration_minutes' => 45,
                    'presenter_id' => $presenterId,
                    'status' => match ($definition['status']) {
                        'draft' => 'pending',
                        'in_progress' => 'in_progress',
                        'completed' => 'completed',
                        'cancelled' => 'cancelled',
                        default => 'pending',
                    },
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );

            $meeting->update(['active_agenda_id' => $agenda->id]);

            MeetingParticipant::query()->updateOrCreate(
                ['meeting_id' => $meeting->id, 'user_id' => $users->first()->id],
                [
                    'role' => 'chair',
                    'position' => 'Chủ trì',
                    'status' => $definition['status'] === 'completed' ? 'present' : 'pending',
                    'checkin_at' => $definition['status'] === 'completed' ? now()->subDays(2) : null,
                    'absence_reason' => null,
                    'delegated_to_id' => null,
                    'sort_order' => 1,
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );

            MeetingDocument::query()->updateOrCreate(
                ['meeting_id' => $meeting->id, 'document_number' => 'DOC-'.$definition['code']],
                [
                    'agenda_id' => $agenda->id,
                    'document_type_id' => $documentTypeId,
                    'document_field_id' => $documentFieldId,
                    'issuing_agency_id' => $issuingAgencyId,
                    'document_signer_id' => $documentSignerId,
                    'title' => 'Tài liệu '.$definition['code'],
                    'description' => 'Tài liệu seed cho '.$definition['title'],
                    'issued_at' => now()->toDateString(),
                    'status' => match ($definition['status']) {
                        'completed' => 'archived',
                        'cancelled' => 'draft',
                        default => 'active',
                    },
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );

            MeetingReminder::query()->updateOrCreate(
                ['meeting_id' => $meeting->id, 'user_id' => $users->first()->id, 'title' => 'Nhắc việc '.$definition['code']],
                [
                    'content' => 'Reminder seed cho '.$definition['title'],
                    'remind_at' => $definition['start_at'],
                    'status' => match ($definition['status']) {
                        'completed' => 'sent',
                        'cancelled' => 'cancelled',
                        default => 'pending',
                    },
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]
            );
        }
    }
}
