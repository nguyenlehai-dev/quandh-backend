<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Events\MeetingAgendaChanged;
use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingAgenda;

class MeetingAgendaService
{
    /** Danh sách chương trình theo thứ tự. */
    public function index(Meeting $meeting)
    {
        return $meeting->agendas()->orderBy('order_index')->get();
    }

    /** Tạo mục nghị sự mới. */
    public function store(Meeting $meeting, array $validated): MeetingAgenda
    {
        // Tự động gán order_index nếu không truyền
        if (! isset($validated['order_index'])) {
            $validated['order_index'] = $meeting->agendas()->max('order_index') + 1;
        }

        return $meeting->agendas()->create($validated);
    }

    /** Cập nhật mục nghị sự. */
    public function update(MeetingAgenda $agenda, array $validated): MeetingAgenda
    {
        $agenda->update($validated);

        return $agenda;
    }

    /** Xóa mục nghị sự. */
    public function destroy(MeetingAgenda $agenda): void
    {
        $agenda->delete();
    }

    /** Sắp xếp lại thứ tự các mục nghị sự. */
    public function reorder(Meeting $meeting, array $ids): void
    {
        foreach ($ids as $index => $id) {
            $meeting->agendas()->where('id', $id)->update(['order_index' => $index]);
        }
    }

    /** Chuyển mục Agenda hiện tại (broadcast real-time). */
    public function setActive(Meeting $meeting, MeetingAgenda $agenda): MeetingAgenda
    {
        event(new MeetingAgendaChanged($meeting, $agenda));

        return $agenda;
    }
}

