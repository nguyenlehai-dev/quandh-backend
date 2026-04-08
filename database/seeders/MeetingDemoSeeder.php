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
use Illuminate\Support\Str;

class MeetingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MeetingCatalogSeeder::class);

        $organizationId = Organization::query()->value('id');
        $users = User::query()->orderBy('id')->limit(5)->get();
        $admin = $users->first();

        if (! $admin) {
            return;
        }

        $meetingType = MeetingType::query()->where('organization_id', $organizationId)->orderBy('id')->first()
            ?: MeetingType::query()->orderBy('id')->first();

        $meeting = Meeting::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'code' => 'MTG-DEMO-001'],
            [
                'meeting_type_id' => $meetingType ? $meetingType->id : null,
                'title' => 'Họp giao ban điều hành tuần',
                'description' => 'Cuộc họp mẫu dùng để kiểm thử đầy đủ các nghiệp vụ Họp không giấy.',
                'location' => 'Phòng họp trực tuyến',
                'start_at' => now()->addDay()->setTime(9, 0),
                'end_at' => now()->addDay()->setTime(11, 0),
                'status' => 'active',
                'qr_token' => optional(Meeting::query()->where('organization_id', $organizationId)->where('code', 'MTG-DEMO-001')->first())->qr_token ?: (string) Str::uuid(),
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $this->seedGroupMembers($users);

        foreach ($users->take(4)->values() as $index => $user) {
            MeetingParticipant::query()->updateOrCreate(
                ['meeting_id' => $meeting->id, 'user_id' => $user->id],
                [
                    'role' => $index === 0 ? 'chair' : ($index === 1 ? 'secretary' : 'delegate'),
                    'position' => $index === 0 ? 'Chủ trì' : ($index === 1 ? 'Thư ký' : 'Đại biểu'),
                    'status' => $index === 0 ? 'present' : 'pending',
                    'checkin_at' => $index === 0 ? now() : null,
                    'absence_reason' => null,
                    'delegated_to_id' => null,
                    'sort_order' => $index + 1,
                ]
            );
        }

        $openingAgenda = MeetingAgenda::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'title' => 'Khai mạc và thông qua chương trình'],
            [
                'description' => 'Chủ trì nêu mục tiêu, phạm vi và chương trình họp.',
                'sort_order' => 1,
                'duration_minutes' => 20,
                'presenter_id' => $admin->id,
                'status' => 'active',
            ]
        );

        $votingAgenda = MeetingAgenda::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'title' => 'Thảo luận và biểu quyết nội dung trọng tâm'],
            [
                'description' => 'Đại biểu thảo luận tài liệu, kết luận và biểu quyết phương án triển khai.',
                'sort_order' => 2,
                'duration_minutes' => 80,
                'presenter_id' => $users->get(1) ? $users->get(1)->id : $admin->id,
                'status' => 'pending',
            ]
        );

        $meeting->update(['active_agenda_id' => $openingAgenda->id]);

        $documentType = MeetingDocumentType::query()->where('organization_id', $organizationId)->orderBy('id')->first();
        $documentField = MeetingDocumentField::query()->where('organization_id', $organizationId)->orderBy('id')->first();
        $documentSigner = MeetingDocumentSigner::query()->where('organization_id', $organizationId)->orderBy('id')->first();
        $issuingAgency = MeetingIssuingAgency::query()->where('organization_id', $organizationId)->orderBy('id')->first();

        $report = MeetingDocument::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'document_number' => 'BC-DEMO-001'],
            [
                'agenda_id' => $votingAgenda->id,
                'document_type_id' => $documentType ? $documentType->id : null,
                'document_field_id' => $documentField ? $documentField->id : null,
                'issuing_agency_id' => $issuingAgency ? $issuingAgency->id : null,
                'document_signer_id' => $documentSigner ? $documentSigner->id : null,
                'title' => 'Báo cáo tình hình triển khai tuần',
                'description' => 'Tài liệu mẫu phục vụ thảo luận trong cuộc họp.',
                'issued_at' => now()->toDateString(),
                'status' => 'published',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        MeetingDocument::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'document_number' => 'DT-DEMO-001'],
            [
                'agenda_id' => $votingAgenda->id,
                'document_type_id' => $documentType ? $documentType->id : null,
                'document_field_id' => $documentField ? $documentField->id : null,
                'issuing_agency_id' => $issuingAgency ? $issuingAgency->id : null,
                'document_signer_id' => $documentSigner ? $documentSigner->id : null,
                'title' => 'Dự thảo kết luận cuộc họp',
                'description' => 'Dự thảo mẫu để đại biểu góp ý trước khi chốt kết luận.',
                'issued_at' => now()->toDateString(),
                'status' => 'draft',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        MeetingConclusion::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'title' => 'Kết luận triển khai nhiệm vụ tuần'],
            [
                'agenda_id' => $votingAgenda->id,
                'content' => 'Các đơn vị cập nhật tiến độ trước 16:00 thứ Sáu và báo cáo nội dung phát sinh về văn phòng tổng hợp.',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $voting = MeetingVoting::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'title' => 'Biểu quyết thông qua kế hoạch tuần'],
            [
                'agenda_id' => $votingAgenda->id,
                'description' => 'Biểu quyết phương án triển khai theo tài liệu đã trình bày.',
                'type' => 'public',
                'status' => 'open',
                'options' => ['agree', 'disagree', 'other'],
                'opened_at' => now(),
                'closed_at' => null,
            ]
        );

        foreach ($users->take(3) as $user) {
            MeetingVoteResult::query()->updateOrCreate(
                ['voting_id' => $voting->id, 'user_id' => $user->id],
                ['option' => 'agree', 'note' => 'Đồng ý với phương án trình bày.']
            );
        }

        MeetingSpeechRequest::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $users->get(2) ? $users->get(2)->id : $admin->id, 'content' => 'Đề xuất bổ sung mốc kiểm tra giữa tuần.'],
            [
                'agenda_id' => $votingAgenda->id,
                'status' => 'approved',
                'review_note' => 'Đã duyệt phát biểu trong phần thảo luận.',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]
        );

        MeetingPersonalNote::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'document_id' => $report->id, 'user_id' => $admin->id],
            ['content' => 'Theo dõi phần báo cáo tiến độ và các đầu việc cần chốt sau cuộc họp.']
        );

        MeetingReminder::query()->updateOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $admin->id, 'title' => 'Chuẩn bị tài liệu họp'],
            [
                'content' => 'Kiểm tra tài liệu, danh sách người dự và trạng thái biểu quyết trước giờ họp.',
                'remind_at' => now()->addDay()->setTime(8, 30),
                'status' => 'pending',
            ]
        );
    }

    protected function seedGroupMembers($users): void
    {
        $groups = AttendeeGroup::query()->orderBy('id')->get();

        foreach ($groups as $index => $group) {
            $user = $users->get($index);

            if (! $user) {
                continue;
            }

            AttendeeGroupMember::query()->updateOrCreate(
                ['attendee_group_id' => $group->id, 'user_id' => $user->id],
                ['position' => $group->name]
            );
        }
    }
}
