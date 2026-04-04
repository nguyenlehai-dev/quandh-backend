<?php

namespace App\Modules\Meeting\Services;

use App\Modules\Meeting\Models\AttendeeGroup;
use App\Modules\Meeting\Models\AttendeeGroupMember;

class AttendeeGroupMemberService
{
    public function index(AttendeeGroup $group)
    {
        return $group->members()->with('user')->get();
    }

    public function store(AttendeeGroup $group, array $validated): AttendeeGroupMember
    {
        return $group->members()->create($validated)->load('user');
    }

    public function update(AttendeeGroupMember $member, array $validated): AttendeeGroupMember
    {
        $member->update($validated);

        return $member->load('user');
    }

    public function destroy(AttendeeGroupMember $member): void
    {
        $member->delete();
    }
}
