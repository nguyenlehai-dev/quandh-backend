<?php

namespace Tests\Feature;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\User;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingAgenda;
use App\Modules\Meeting\Models\MeetingParticipant;
use App\Modules\Meeting\Models\MeetingReminder;
use App\Modules\Meeting\Models\MeetingSpeechRequest;
use App\Modules\Meeting\Models\MeetingVoteResult;
use App\Modules\Meeting\Models\MeetingVoting;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MeetingModuleFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_admin_can_create_and_list_meeting_types(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $user = User::factory()->active()->create();

        $this->attachUserToOrganization($user, $organization, 'meeting-admin', [
            'meeting-types.index',
            'meeting-types.store',
        ]);

        Sanctum::actingAs($user);

        $storeResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson('/api/meeting-types', [
                'name' => 'Họp giao ban',
                'description' => 'Loại họp nội bộ',
                'status' => 'active',
            ]);

        $storeResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Họp giao ban');

        $this->assertDatabaseHas('m_meeting_types', [
            'organization_id' => $organization->id,
            'name' => 'Họp giao ban',
        ]);

        $indexResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/meeting-types');

        $indexResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['name' => 'Họp giao ban']);
    }

    public function test_participant_cannot_view_meeting_when_not_invited(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $user = User::factory()->active()->create();

        $this->attachUserToOrganization($user, $organization, 'meeting-participant', [
            'my-meetings.show',
        ]);

        $meeting = Meeting::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Cuộc họp kín',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/participant/meetings/{$meeting->id}");

        $response->assertForbidden();
    }

    public function test_participant_self_checkin_updates_attendance_and_creates_checkin_log(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $user = User::factory()->active()->create();

        $this->attachUserToOrganization($user, $organization, 'meeting-participant', [
            'my-meetings.checkin',
            'my-meetings.show',
        ]);

        $meeting = Meeting::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Cuộc họp điều hành',
            'status' => 'active',
            'checkin_opened_at' => now(),
        ]);

        $participant = MeetingParticipant::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
            'meeting_role' => 'delegate',
            'attendance_status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/participant/meetings/{$meeting->id}/self-checkin");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meeting_id', $meeting->id)
            ->assertJsonPath('data.participant_id', $participant->id)
            ->assertJsonPath('data.attendance_status', 'present');

        $this->assertDatabaseHas('m_participants', [
            'id' => $participant->id,
            'attendance_status' => 'present',
        ]);

        $this->assertDatabaseHas('m_checkins', [
            'meeting_id' => $meeting->id,
            'meeting_participant_id' => $participant->id,
            'type' => 'self',
        ]);
    }

    public function test_admin_can_set_active_agenda_and_live_endpoint_returns_it(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $user = User::factory()->active()->create();

        $this->attachUserToOrganization($user, $organization, 'meeting-operator', [
            'meeting-agendas.set-active',
            'meetings.live-control',
        ]);

        $meeting = Meeting::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Cuộc họp chuyên đề',
            'status' => 'in_progress',
        ]);

        $agendaA = MeetingAgenda::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'title' => 'Nội dung 1',
            'order_index' => 1,
        ]);

        $agendaB = MeetingAgenda::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'title' => 'Nội dung 2',
            'order_index' => 2,
        ]);

        Sanctum::actingAs($user);

        $setActiveResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->patchJson("/api/meetings/{$meeting->id}/agendas/{$agendaB->id}/set-active");

        $setActiveResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $agendaB->id)
            ->assertJsonPath('data.is_active', true);

        $liveResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/admin/meetings/{$meeting->id}/live");

        $liveResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_agenda.id', $agendaB->id);

        $this->assertDatabaseHas('m_meetings', [
            'id' => $meeting->id,
            'active_agenda_id' => $agendaB->id,
        ]);
    }

    public function test_participant_can_crud_personal_notes_for_invited_meeting(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $user = User::factory()->active()->create();

        $this->attachUserToOrganization($user, $organization, 'meeting-note-user', [
            'my-meetings.note',
            'my-meetings.show',
        ]);

        $meeting = Meeting::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Cuộc họp ghi chú',
            'status' => 'active',
        ]);

        MeetingParticipant::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
            'meeting_role' => 'delegate',
            'attendance_status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $storeResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/participant/meetings/{$meeting->id}/personal-notes", [
                'content' => 'Ghi chú ban đầu',
            ]);

        $storeResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Ghi chú ban đầu');

        $noteId = (int) $storeResponse->json('data.id');

        $this->assertDatabaseHas('m_personal_notes', [
            'id' => $noteId,
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
            'content' => 'Ghi chú ban đầu',
        ]);

        $indexResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/participant/meetings/{$meeting->id}/personal-notes");

        $indexResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['content' => 'Ghi chú ban đầu']);

        $updateResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->putJson("/api/participant/meetings/{$meeting->id}/personal-notes/{$noteId}", [
                'content' => 'Ghi chú đã cập nhật',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Ghi chú đã cập nhật');

        $this->assertDatabaseHas('m_personal_notes', [
            'id' => $noteId,
            'content' => 'Ghi chú đã cập nhật',
        ]);

        $deleteResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->deleteJson("/api/participant/meetings/{$meeting->id}/personal-notes/{$noteId}");

        $deleteResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('m_personal_notes', [
            'id' => $noteId,
        ]);
    }

    public function test_participant_can_create_and_list_own_speech_requests(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $user = User::factory()->active()->create();

        $this->attachUserToOrganization($user, $organization, 'meeting-speaker', [
            'my-meetings.speech-request',
            'my-meetings.show',
        ]);

        $meeting = Meeting::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Cuộc họp phát biểu',
            'status' => 'in_progress',
        ]);

        $agenda = MeetingAgenda::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'title' => 'Nội dung thảo luận',
            'order_index' => 1,
        ]);

        MeetingParticipant::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
            'meeting_role' => 'delegate',
            'attendance_status' => 'present',
        ]);

        Sanctum::actingAs($user);

        $storeResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/participant/meetings/{$meeting->id}/speech-requests", [
                'meeting_agenda_id' => $agenda->id,
                'content' => 'Xin đăng ký phát biểu về mục 1',
            ]);

        $storeResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meeting_id', $meeting->id)
            ->assertJsonPath('data.meeting_agenda_id', $agenda->id)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('m_speech_requests', [
            'meeting_id' => $meeting->id,
            'meeting_agenda_id' => $agenda->id,
            'status' => 'pending',
            'content' => 'Xin đăng ký phát biểu về mục 1',
        ]);

        $mineResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/participant/meetings/{$meeting->id}/speech-requests/mine");

        $mineResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['content' => 'Xin đăng ký phát biểu về mục 1']);
    }

    public function test_participant_can_vote_and_view_public_voting_results(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $user = User::factory()->active()->create();

        $this->attachUserToOrganization($user, $organization, 'meeting-voter', [
            'my-meetings.vote',
            'my-meetings.show',
        ]);

        $meeting = Meeting::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Cuộc họp biểu quyết',
            'status' => 'in_progress',
        ]);

        $agenda = MeetingAgenda::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'title' => 'Biểu quyết nội dung',
            'order_index' => 1,
        ]);

        MeetingParticipant::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'user_id' => $user->id,
            'meeting_role' => 'delegate',
            'attendance_status' => 'present',
        ]);

        $voting = MeetingVoting::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'meeting_agenda_id' => $agenda->id,
            'title' => 'Thông qua nghị quyết',
            'type' => 'public',
            'status' => 'open',
            'opened_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $currentVotingResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/participant/meetings/{$meeting->id}/votings/current");

        $currentVotingResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $voting->id);

        $voteResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/participant/meetings/{$meeting->id}/votings/{$voting->id}/vote", [
                'choice' => 'agree',
            ]);

        $voteResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.choice', 'agree');

        $this->assertDatabaseHas('m_vote_results', [
            'meeting_voting_id' => $voting->id,
            'user_id' => $user->id,
            'choice' => 'agree',
        ]);

        $resultResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/participant/meetings/{$meeting->id}/votings/{$voting->id}/result");

        $resultResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.voting_id', $voting->id)
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.agree', 1)
            ->assertJsonPath('data.details.0.user_id', $user->id)
            ->assertJsonPath('data.details.0.choice', 'agree');
    }

    public function test_admin_can_crud_meeting_reminders(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $user = User::factory()->active()->create();

        $this->attachUserToOrganization($user, $organization, 'meeting-reminder-admin', [
            'meeting-reminders.index',
            'meeting-reminders.store',
            'meeting-reminders.update',
            'meeting-reminders.destroy',
        ]);

        $meeting = Meeting::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Cuộc họp nhắc lịch',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $storeResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/meetings/{$meeting->id}/reminders", [
                'channel' => 'database',
                'remind_at' => now()->addHour()->toISOString(),
                'payload' => ['title' => 'Nhắc họp'],
            ]);

        $storeResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.channel', 'database')
            ->assertJsonPath('data.status', 'pending');

        $reminderId = (int) $storeResponse->json('data.id');

        $this->assertDatabaseHas('m_reminders', [
            'id' => $reminderId,
            'meeting_id' => $meeting->id,
            'channel' => 'database',
            'status' => 'pending',
        ]);

        $indexResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/meetings/{$meeting->id}/reminders");

        $indexResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['id' => $reminderId, 'channel' => 'database']);

        $updateResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->putJson("/api/meetings/{$meeting->id}/reminders/{$reminderId}", [
                'status' => 'cancelled',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('m_reminders', [
            'id' => $reminderId,
            'status' => 'cancelled',
        ]);

        $deleteResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->deleteJson("/api/meetings/{$meeting->id}/reminders/{$reminderId}");

        $deleteResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('m_reminders', [
            'id' => $reminderId,
        ]);
    }

    public function test_admin_can_approve_and_reject_speech_requests(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $admin = User::factory()->active()->create();
        $participantUser = User::factory()->active()->create();

        $this->attachUserToOrganization($admin, $organization, 'meeting-speech-admin', [
            'meeting-speech-requests.approve',
            'meeting-speech-requests.reject',
        ]);

        $meeting = Meeting::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Cuộc họp điều hành phát biểu',
            'status' => 'in_progress',
        ]);

        $participant = MeetingParticipant::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'user_id' => $participantUser->id,
            'meeting_role' => 'delegate',
            'attendance_status' => 'present',
        ]);

        $speechRequest = MeetingSpeechRequest::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'meeting_participant_id' => $participant->id,
            'content' => 'Xin phát biểu',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $approveResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->patchJson("/api/meetings/{$meeting->id}/speech-requests/{$speechRequest->id}/approve");

        $approveResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('m_speech_requests', [
            'id' => $speechRequest->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        $speechRequest->update([
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $rejectResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->patchJson("/api/meetings/{$meeting->id}/speech-requests/{$speechRequest->id}/reject");

        $rejectResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'rejected');

        $this->assertDatabaseHas('m_speech_requests', [
            'id' => $speechRequest->id,
            'status' => 'rejected',
        ]);
    }

    public function test_admin_can_open_close_voting_and_view_results(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $admin = User::factory()->active()->create();
        $voter = User::factory()->active()->create();

        $this->attachUserToOrganization($admin, $organization, 'meeting-voting-admin', [
            'meeting-votings.open',
            'meeting-votings.close',
            'meeting-votings.results',
        ]);

        $meeting = Meeting::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Cuộc họp admin biểu quyết',
            'status' => 'in_progress',
        ]);

        MeetingParticipant::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'user_id' => $voter->id,
            'meeting_role' => 'delegate',
            'attendance_status' => 'present',
        ]);

        $voting = MeetingVoting::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'title' => 'Biểu quyết ngân sách',
            'type' => 'public',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $openResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->patchJson("/api/meetings/{$meeting->id}/votings/{$voting->id}/open");

        $openResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'open');

        MeetingVoteResult::query()->create([
            'organization_id' => $organization->id,
            'meeting_voting_id' => $voting->id,
            'user_id' => $voter->id,
            'choice' => 'agree',
        ]);

        $resultResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/meetings/{$meeting->id}/votings/{$voting->id}/results");

        $resultResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.agree', 1);

        $closeResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->patchJson("/api/meetings/{$meeting->id}/votings/{$voting->id}/close");

        $closeResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseHas('m_votings', [
            'id' => $voting->id,
            'status' => 'closed',
        ]);
    }

    public function test_admin_can_manage_attendee_groups_and_members(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $admin = User::factory()->active()->create();
        $memberUser = User::factory()->active()->create();

        $this->attachUserToOrganization($admin, $organization, 'meeting-attendee-group-admin', [
            'attendee-groups.index',
            'attendee-groups.store',
            'attendee-groups.update',
            'attendee-group-members.index',
            'attendee-group-members.store',
            'attendee-group-members.update',
            'attendee-group-members.destroy',
        ]);

        Sanctum::actingAs($admin);

        $storeGroupResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson('/api/attendee-groups', [
                'name' => 'Ban điều hành',
                'description' => 'Nhóm lãnh đạo chủ trì',
                'status' => 'active',
            ]);

        $storeGroupResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Ban điều hành');

        $groupId = (int) $storeGroupResponse->json('data.id');

        $storeMemberResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson("/api/attendee-groups/{$groupId}/members", [
                'user_id' => $memberUser->id,
                'position' => 'Trưởng ban',
            ]);

        $storeMemberResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $memberUser->id)
            ->assertJsonPath('data.position', 'Trưởng ban');

        $memberId = (int) $storeMemberResponse->json('data.id');

        $indexMemberResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/attendee-groups/{$groupId}/members");

        $indexMemberResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['user_id' => $memberUser->id, 'position' => 'Trưởng ban']);

        $updateMemberResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->putJson("/api/attendee-groups/{$groupId}/members/{$memberId}", [
                'position' => 'Phó ban',
            ]);

        $updateMemberResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.position', 'Phó ban');

        $deleteMemberResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->deleteJson("/api/attendee-groups/{$groupId}/members/{$memberId}");

        $deleteMemberResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('m_attendee_groups', [
            'id' => $groupId,
            'organization_id' => $organization->id,
            'name' => 'Ban điều hành',
        ]);

        $this->assertDatabaseMissing('m_attendee_group_members', [
            'id' => $memberId,
        ]);
    }

    public function test_admin_can_view_dashboard_reports_candidates_and_qr_token(): void
    {
        $organization = Organization::factory()->create(['status' => 'active']);
        $admin = User::factory()->active()->create();
        $participantUser = User::factory()->active()->create();
        $extraUser = User::factory()->active()->create();

        $this->attachUserToOrganization($admin, $organization, 'meeting-report-admin', [
            'meetings.dashboard',
            'meetings.index',
            'meetings.show',
            'meetings.live-control',
            'meeting-participants.index',
        ]);

        $meeting = Meeting::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Cuộc họp tổng hợp',
            'status' => 'active',
            'start_at' => now()->addDay(),
        ]);

        MeetingParticipant::query()->create([
            'organization_id' => $organization->id,
            'meeting_id' => $meeting->id,
            'user_id' => $participantUser->id,
            'meeting_role' => 'delegate',
            'attendance_status' => 'present',
        ]);

        Sanctum::actingAs($admin);

        $dashboardResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/admin/meetings/dashboard');

        $dashboardResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.active', 1);

        $reportResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/admin/meetings/reports');

        $reportResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meetings_by_status.active', 1)
            ->assertJsonPath('data.participant_summary.total', 1)
            ->assertJsonPath('data.participant_summary.present', 1);

        $candidatesResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/admin/meetings/{$meeting->id}/participant-candidates");

        $candidatesResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['id' => $extraUser->id])
            ->assertJsonMissing(['id' => $participantUser->id]);

        $qrResponse = $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson("/api/admin/meetings/{$meeting->id}/qr-token");

        $qrResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meeting_id', $meeting->id);

        $this->assertNotEmpty($qrResponse->json('data.qr_token'));
    }

    private function attachUserToOrganization(
        User $user,
        Organization $organization,
        string $roleName,
        array $permissionNames = []
    ): void {
        $role = Role::query()->create([
            'name' => $roleName.'-'.$organization->id.'-'.$user->id,
            'guard_name' => 'web',
            'organization_id' => $organization->id,
        ]);

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $role->givePermissionTo($permission);
        }

        setPermissionsTeamId($organization->id);
        $user->assignRole($role);
        setPermissionsTeamId(null);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
