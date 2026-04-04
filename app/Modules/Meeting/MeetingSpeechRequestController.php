<?php

namespace App\Modules\Meeting;

use App\Http\Controllers\Controller;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingSpeechRequest;
use App\Modules\Meeting\Models\MeetingParticipant;
use App\Modules\Meeting\Services\MeetingSpeechRequestService;
use Illuminate\Http\Request;

/**
 * @group Meeting - Đăng ký phát biểu
 * @header X-Organization-Id 1
 *
 * Quản lý đăng ký phát biểu trong cuộc họp: nộp, duyệt, từ chối.
 */
class MeetingSpeechRequestController extends Controller
{
    public function __construct(private MeetingSpeechRequestService $service) {}

    /**
     * Danh sách đăng ký phát biểu
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     */
    public function index(Meeting $meeting)
    {
        $requests = $this->service->index($meeting);

        return $this->success($requests);
    }

    /**
     * Nộp đăng ký phát biểu
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     *
     * @bodyParam meeting_agenda_id integer ID mục nghị sự cần phát biểu. Example: 1
     * @bodyParam content string Nội dung dự kiến phát biểu.
     */
    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'meeting_agenda_id' => 'nullable|integer|exists:m_agendas,id',
            'content' => 'nullable|string',
        ]);

        $speechRequest = $this->service->store($meeting, $validated);

        return $this->success($speechRequest, 'Đã nộp đăng ký phát biểu!');
    }

    /**
     * Duyệt đăng ký phát biểu
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam speechRequest integer required ID đăng ký. Example: 1
     */
    public function approve(Meeting $meeting, MeetingSpeechRequest $speechRequest)
    {
        abort_unless((int) $speechRequest->meeting_id === (int) $meeting->id, 422, 'Đăng ký phát biểu không thuộc cuộc họp này.');
        $speechRequest = $this->service->updateStatus($speechRequest, 'approved');

        return $this->success($speechRequest, 'Đã duyệt đăng ký phát biểu!');
    }

    /**
     * Từ chối đăng ký phát biểu
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam speechRequest integer required ID đăng ký. Example: 1
     */
    public function reject(Meeting $meeting, MeetingSpeechRequest $speechRequest)
    {
        abort_unless((int) $speechRequest->meeting_id === (int) $meeting->id, 422, 'Đăng ký phát biểu không thuộc cuộc họp này.');
        $speechRequest = $this->service->updateStatus($speechRequest, 'rejected');

        return $this->success($speechRequest, 'Đã từ chối đăng ký phát biểu!');
    }

    /**
     * Xóa đăng ký phát biểu
     *
     * @urlParam meeting integer required ID cuộc họp. Example: 1
     * @urlParam speechRequest integer required ID đăng ký. Example: 1
     */
    public function destroy(Meeting $meeting, MeetingSpeechRequest $speechRequest)
    {
        abort_unless((int) $speechRequest->meeting_id === (int) $meeting->id, 422, 'Đăng ký phát biểu không thuộc cuộc họp này.');
        $this->service->destroy($speechRequest);

        return $this->success(null, 'Đã xóa đăng ký phát biểu!');
    }
}

