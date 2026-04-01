<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use App\Modules\Meeting\Models\MeetingType;
use App\Modules\Meeting\Models\AttendeeGroup;
use App\Modules\Document\Models\DocumentType;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingParticipant;
use App\Modules\Meeting\Models\MeetingAgenda;
use App\Modules\Meeting\Models\MeetingVoting;
use App\Modules\Meeting\Models\MeetingConclusion;
use Carbon\Carbon;

class MeetingDummySeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks to allow clearing tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'm_conclusions',
            'm_vote_results',
            'm_votings',
            'm_speech_requests',
            'm_personal_notes',
            'm_documents',
            'm_agendas',
            'm_participants',
            'm_meetings',
            'm_attendee_group_members',
            'm_attendee_groups',
            'm_meeting_types',
            'document_types',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Fetch primary admin user
        $admin = DB::table('users')->where('email', 'admin@snvdn.com')->first();
        if (!$admin) {
            $admin = DB::table('users')->first();
        }
        if (!$admin) {
            echo "No users found in the system.\n";
            return;
        }

        $organizationId = DB::table('organizations')->value('id') ?: null;

        $allUsers = DB::table('users')->select('id', 'name')->take(20)->get();

        // Data definition
        $meetingTypesData = [
            'Họp Giao Ban Đơn Vị' => [
                'groups' => ['Ban giám đốc', 'Trưởng/Phó các phòng ban', 'Toàn bộ nhân viên'],
                'doc_types' => ['Báo cáo tuần', 'Kế hoạch công tác tuần tới', 'Tài liệu chỉ đạo', 'Biên bản họp giao ban'],
                'titles' => ['Họp giao ban tuần 1', 'Họp giao ban thường kỳ tháng 3', 'Họp giao ban triển khai công việc'],
            ],
            'Họp Hội Đồng Quản Trị' => [
                'groups' => ['Thành viên HĐQT', 'Ban kiểm soát', 'Khách mời Cổ đông'],
                'doc_types' => ['Báo cáo tài chính quý', 'Đề án chiến lược', 'Quyết định đầu tư', 'Nghị quyết HĐQT'],
                'titles' => ['Họp HĐQT Quý 1/2026', 'Họp thông qua phương án tài chính năm', 'Họp bất thường HĐQT'],
            ],
            'Đại Hội Cổ Đông' => [
                'groups' => ['Cổ đông chiến lược', 'Cổ đông lớn', 'Ban điều hành'],
                'doc_types' => ['Báo cáo thường niên', 'Quy chế công ty bổ sung', 'Biên bản ĐHCĐ'],
                'titles' => ['Đại hội đồng cổ đông thường niên 2026', 'Đại hội cổ đông bất thường'],
            ],
            'Họp Chuyên Đề / DA' => [
                'groups' => ['Ban quản lý dự án', 'Ban công nghệ', 'Đối tác thi công'],
                'doc_types' => ['Tài liệu kỹ thuật thiết kế', 'Tiến độ giải ngân', 'Hợp đồng nguyên tắc'],
                'titles' => ['Họp rà soát tiến độ dự án A', 'Họp thống nhất kỹ thuật thi công', 'Họp nghiệm thu giai đoạn 1'],
            ],
        ];

        $now = Carbon::now();
        $statuses = ['active', 'in_progress', 'completed', 'draft'];

        foreach ($meetingTypesData as $typeName => $typeData) {
            // Create Meeting Type
            $typeId = DB::table('m_meeting_types')->insertGetId([
                'name' => $typeName,
                'description' => 'Mô tả hệ thống cho: ' . $typeName,
                'status' => 'active',
                'organization_id' => $organizationId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Create Attendee Groups
            $groupIds = [];
            foreach ($typeData['groups'] as $groupName) {
                $groupId = DB::table('m_attendee_groups')->insertGetId([
                    'name' => $groupName,
                    'description' => 'Nhóm người dự họp cho: ' . $groupName,
                    'status' => 'active',
                    'meeting_type_id' => $typeId,
                    'organization_id' => $organizationId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $groupIds[] = $groupId;

                // Add members to the group
                $members = $allUsers->random(min(10, $allUsers->count()));
                foreach ($members as $index => $member) {
                    DB::table('m_attendee_group_members')->insert([
                        'attendee_group_id' => $groupId,
                        'user_id' => $member->id,
                        'position' => 'Thành viên',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // Create Document Types
            foreach ($typeData['doc_types'] as $docTypeName) {
                DB::table('document_types')->insert([
                    'name' => $docTypeName,
                    'description' => 'Tài liệu thuộc phân loại: ' . $typeName,
                    'status' => 'active',
                    'meeting_type_id' => $typeId,
                    'organization_id' => $organizationId,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Create Meetings
            foreach ($typeData['titles'] as $k => $title) {
                for ($m = 0; $m < 3; $m++) {
                    // Create 3 variations of each title
                    $status = collect($statuses)->random();
                    $startDate = Carbon::now()->addDays(rand(-30, 30))->setHour(rand(8, 15))->setMinute(0)->setSecond(0);
                    $endDate = $startDate->copy()->addHours(rand(1, 4));

                    $meetingId = DB::table('m_meetings')->insertGetId([
                        'title' => $title . ($m > 0 ? ' (Phiên ' . ($m + 1) . ')' : ''),
                        'description' => 'Mô tả chi tiết nội dung ' . $title,
                        'location' => 'Phòng họp ' . rand(101, 505) . ', Tòa nhà A',
                        'start_at' => $startDate,
                        'end_at' => $endDate,
                        'status' => $status,
                        'qr_token' => $status !== 'draft' ? strtoupper(\Illuminate\Support\Str::random(12)) : null,
                        'meeting_type_id' => $typeId,
                        'organization_id' => $organizationId,
                        'created_by' => $admin->id,
                        'updated_by' => $admin->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    // Add Participants
                    $participants = $allUsers->random(min(8, $allUsers->count()));
                    foreach ($participants as $pIndex => $participant) {
                        $role = $pIndex === 0 ? 'chair' : ($pIndex === 1 ? 'secretary' : 'delegate');
                        $attendance = $status === 'draft' ? 'pending' : collect(['present', 'present', 'absent', 'pending'])->random();

                        DB::table('m_participants')->insert([
                            'meeting_id' => $meetingId,
                            'user_id' => $participant->id,
                            'meeting_role' => $role,
                            'attendance_status' => $attendance,
                            'checkin_at' => $attendance === 'present' ? $startDate->copy()->addMinutes(rand(-10, 10)) : null,
                            'organization_id' => $organizationId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    // Add Agendas
                    for ($a = 0; $a < rand(2, 5); $a++) {
                        $agendaId = DB::table('m_agendas')->insertGetId([
                            'meeting_id' => $meetingId,
                            'title' => 'Mục ' . ($a + 1) . ': Báo cáo và thảo luận ' . fake()->sentence(3),
                            'description' => 'Chi tiết triển khai mục ' . ($a + 1),
                            'order_index' => $a + 1,
                            'duration' => rand(15, 60),
                            'organization_id' => $organizationId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);

                        // Add Voting occasionally
                        if ($status !== 'draft' && rand(1, 10) > 6) {
                            $voteTitle = 'Biểu quyết thông qua mục ' . ($a + 1);
                            $voteState = $status === 'completed' ? 'closed' : collect(['draft', 'open', 'closed'])->random();

                            $voteId = DB::table('m_votings')->insertGetId([
                                'meeting_id' => $meetingId,
                                'meeting_agenda_id' => $agendaId,
                                'title' => $voteTitle,
                                'description' => 'Mời các đại biểu cho ý kiến biểu quyết',
                                'type' => collect(['public', 'anonymous'])->random(),
                                'status' => $voteState,
                                'organization_id' => $organizationId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);

                            if ($voteState === 'closed') {
                                foreach ($participants as $participant) {
                                    DB::table('m_vote_results')->insert([
                                        'meeting_voting_id' => $voteId,
                                        'user_id' => $participant->id,
                                        'choice' => collect(['agree', 'agree', 'disagree', 'abstain'])->random(),
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                    ]);
                                }
                            }
                        }

                        // Add Conclusions occasionally for completed meetings
                        if ($status === 'completed' && rand(1, 10) > 5) {
                            DB::table('m_conclusions')->insert([
                                'meeting_id' => $meetingId,
                                'meeting_agenda_id' => $agendaId,
                                'title' => 'Kết luận mục ' . ($a+1),
                                'content' => fake()->paragraph(),
                                'created_by' => $admin->id,
                                'updated_by' => $admin->id,
                                'organization_id' => $organizationId,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                }
            }
        }

        echo "Meeting dummy data has been seeded successfully!\n";
    }
}
