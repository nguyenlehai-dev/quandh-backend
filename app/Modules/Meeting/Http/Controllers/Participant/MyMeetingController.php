<?php

namespace App\Modules\Meeting\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingPersonalNote;
use App\Modules\Meeting\Models\MeetingVoting;
use App\Modules\Meeting\Resources\MeetingConclusionResource;
use App\Modules\Meeting\Resources\MeetingDocumentResource;
use App\Modules\Meeting\Resources\MeetingCollection;
use App\Modules\Meeting\Resources\MeetingPersonalNoteResource;
use App\Modules\Meeting\Resources\MeetingResource;
use App\Modules\Meeting\Resources\MeetingVotingResource;
use App\Modules\Meeting\Services\MeetingConclusionService;
use App\Modules\Meeting\Services\MeetingDocumentService;
use App\Modules\Meeting\Services\MeetingPersonalNoteService;
use App\Modules\Meeting\Services\MeetingService;
use App\Modules\Meeting\Services\MeetingSpeechRequestService;
use App\Modules\Meeting\Services\MeetingVotingService;
use Illuminate\Http\Request;

class MyMeetingController extends Controller
{
    public function __construct(
        private MeetingService $meetingService,
        private MeetingDocumentService $meetingDocumentService,
        private MeetingPersonalNoteService $meetingPersonalNoteService,
        private MeetingConclusionService $meetingConclusionService,
        private MeetingSpeechRequestService $meetingSpeechRequestService,
        private MeetingVotingService $meetingVotingService,
    ) {}

    public function index(FilterRequest $request)
    {
        $meetings = $this->meetingService->participantMeetings(
            $request->user(),
            $request->all(),
            (int) ($request->limit ?? 10)
        );

        return $this->successCollection(new MeetingCollection($meetings));
    }

    public function show(Meeting $meeting, Request $request)
    {
        return $this->successResource(
            new MeetingResource($this->meetingService->participantShow($meeting, $request->user()))
        );
    }

    public function selfCheckin(Meeting $meeting, Request $request)
    {
        $result = $this->meetingService->selfCheckin($meeting, $request->user(), 'self');

        return $this->success($result, 'Check-in thành công.');
    }

    public function qrCheckin(Meeting $meeting, Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        abort_unless($meeting->qr_token && hash_equals($meeting->qr_token, (string) $request->token), 422, 'QR token không hợp lệ.');

        $result = $this->meetingService->selfCheckin($meeting, $request->user(), 'qr');

        return $this->success($result, 'Check-in QR thành công.');
    }

    public function documents(Meeting $meeting, Request $request)
    {
        $this->meetingService->ensureParticipantAccess($meeting, $request->user());

        return $this->success(
            MeetingDocumentResource::collection($this->meetingDocumentService->index($meeting))
        );
    }

    public function personalNotes(Meeting $meeting, Request $request)
    {
        $this->meetingService->ensureParticipantAccess($meeting, $request->user());

        return $this->success(
            MeetingPersonalNoteResource::collection($this->meetingPersonalNoteService->index($meeting))
        );
    }

    public function storePersonalNote(Meeting $meeting, Request $request)
    {
        $this->meetingService->ensureParticipantAccess($meeting, $request->user());

        $validated = $request->validate([
            'meeting_document_id' => 'nullable|integer|exists:m_documents,id',
            'content' => 'required|string',
        ]);

        $note = $this->meetingPersonalNoteService->store($meeting, $validated);

        return $this->successResource(new MeetingPersonalNoteResource($note), 'Đã lưu ghi chú cá nhân.', 201);
    }

    public function updatePersonalNote(Meeting $meeting, MeetingPersonalNote $note, Request $request)
    {
        $this->meetingService->ensureParticipantAccess($meeting, $request->user());
        abort_unless((int) $note->meeting_id === (int) $meeting->id, 422, 'Ghi chú không thuộc cuộc họp này.');

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $note = $this->meetingPersonalNoteService->update($note, $validated);

        return $this->successResource(new MeetingPersonalNoteResource($note), 'Đã cập nhật ghi chú cá nhân.');
    }

    public function destroyPersonalNote(Meeting $meeting, MeetingPersonalNote $note, Request $request)
    {
        $this->meetingService->ensureParticipantAccess($meeting, $request->user());
        abort_unless((int) $note->meeting_id === (int) $meeting->id, 422, 'Ghi chú không thuộc cuộc họp này.');
        $this->meetingPersonalNoteService->destroy($note);

        return $this->success(null, 'Đã xóa ghi chú cá nhân.');
    }

    public function conclusions(Meeting $meeting, Request $request)
    {
        $this->meetingService->ensureParticipantAccess($meeting, $request->user());

        return $this->success(
            MeetingConclusionResource::collection($this->meetingConclusionService->index($meeting))
        );
    }

    public function speechRequestsMine(Meeting $meeting, Request $request)
    {
        $this->meetingService->ensureParticipantAccess($meeting, $request->user());

        return $this->success(
            $this->meetingSpeechRequestService->mine($meeting)
        );
    }

    public function storeSpeechRequest(Meeting $meeting, Request $request)
    {
        $this->meetingService->ensureParticipantAccess($meeting, $request->user());

        $validated = $request->validate([
            'meeting_agenda_id' => 'nullable|integer|exists:m_agendas,id',
            'content' => 'nullable|string',
        ]);

        $speechRequest = $this->meetingSpeechRequestService->store($meeting, $validated);

        return $this->success($speechRequest, 'Đã gửi đăng ký phát biểu.', 201);
    }

    public function currentVoting(Meeting $meeting, Request $request)
    {
        $this->meetingService->ensureParticipantAccess($meeting, $request->user());

        $voting = $this->meetingVotingService->currentVoting($meeting);

        return $this->success($voting ? new MeetingVotingResource($voting->load(['agenda', 'results'])) : null);
    }

    public function vote(Meeting $meeting, MeetingVoting $voting, Request $request)
    {
        $this->meetingService->ensureParticipantAccess($meeting, $request->user());
        abort_unless((int) $voting->meeting_id === (int) $meeting->id, 422, 'Phiên biểu quyết không thuộc cuộc họp này.');

        $validated = $request->validate([
            'choice' => 'required|in:agree,disagree,abstain',
        ]);

        $result = $this->meetingVotingService->vote($voting, $validated['choice']);

        return $this->success($result, 'Bỏ phiếu thành công.');
    }

    public function votingResult(Meeting $meeting, MeetingVoting $voting, Request $request)
    {
        $this->meetingService->ensureParticipantAccess($meeting, $request->user());
        abort_unless((int) $voting->meeting_id === (int) $meeting->id, 422, 'Phiên biểu quyết không thuộc cuộc họp này.');

        return $this->success($this->meetingVotingService->results($voting));
    }
}
