<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\Meeting;
use App\Modules\Meeting\Models\MeetingReminder;

class MeetingReminderService
{
    public function index(Meeting $meeting)
    {
        return $meeting->reminders()->latest('remind_at')->get();
    }

    public function store(Meeting $meeting, array $validated): MeetingReminder
    {
        $validated['organization_id'] = $meeting->organization_id;
        $validated['status'] = $validated['status'] ?? 'pending';

        return $meeting->reminders()->create($validated);
    }

    public function update(MeetingReminder $reminder, array $validated): MeetingReminder
    {
        $reminder->update($validated);

        return $reminder->fresh();
    }

    public function destroy(MeetingReminder $reminder): void
    {
        $reminder->delete();
    }
}
