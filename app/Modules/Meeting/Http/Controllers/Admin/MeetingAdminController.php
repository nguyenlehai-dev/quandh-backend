<?php

namespace App\Modules\Meeting\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Core\Requests\FilterRequest;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Services\MeetingConclusionService;
use App\Modules\Meeting\Services\MeetingDocumentService;
use App\Modules\Meeting\Resources\MeetingCollection;
use App\Modules\Meeting\Resources\MeetingConclusionResource;
use App\Modules\Meeting\Resources\MeetingDocumentResource;
use App\Modules\Meeting\Resources\MeetingResource;
use App\Modules\Meeting\Services\MeetingService;
use App\Modules\Meeting\Services\MeetingVotingService;
use Illuminate\Http\Request;

class MeetingAdminController extends Controller
{
    public function __construct(
        private MeetingService $meetingService,
        private MeetingDocumentService $meetingDocumentService,
        private MeetingConclusionService $meetingConclusionService,
        private MeetingVotingService $meetingVotingService,
    ) {}

    public function dashboard(FilterRequest $request)
    {
        return $this->success($this->meetingService->dashboard($request->all()));
    }

    public function index(FilterRequest $request)
    {
        $meetings = $this->meetingService->index($request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(new MeetingCollection($meetings));
    }

    public function show(Meeting $meeting)
    {
        return $this->successResource(new MeetingResource($this->meetingService->show($meeting)));
    }

    public function live(Meeting $meeting)
    {
        return $this->success($this->meetingService->live($meeting));
    }

    public function reports(FilterRequest $request)
    {
        return $this->success($this->meetingService->reports($request->all()));
    }

    public function allDocuments(FilterRequest $request)
    {
        $documents = $this->meetingDocumentService->allDocuments($request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(MeetingDocumentResource::collection($documents));
    }

    public function allConclusions(FilterRequest $request)
    {
        $conclusions = $this->meetingConclusionService->allConclusions($request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(MeetingConclusionResource::collection($conclusions));
    }

    public function allVotings(FilterRequest $request)
    {
        $votings = $this->meetingVotingService->allVotings($request->all(), (int) ($request->limit ?? 10));

        return $this->successCollection(\App\Modules\Meeting\Resources\MeetingVotingResource::collection($votings));
    }

    public function participantCandidates(Meeting $meeting)
    {
        return $this->success($this->meetingService->participantCandidates($meeting));
    }

    public function qrToken(Meeting $meeting)
    {
        $meeting = $this->meetingService->ensureQrToken($meeting);

        return $this->success([
            'meeting_id' => $meeting->id,
            'qr_token' => $meeting->qr_token,
        ]);
    }

    public function qrCheckin(Request $request, Meeting $meeting)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        abort_unless($meeting->qr_token && hash_equals($meeting->qr_token, (string) $request->token), 422, 'QR token không hợp lệ.');

        $result = $this->meetingService->selfCheckin($meeting, $request->user(), 'qr');

        return $this->success($result, 'Check-in QR thành công.');
    }
}
