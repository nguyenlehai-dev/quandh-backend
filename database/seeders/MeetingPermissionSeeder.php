<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MeetingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'meetings.index','meetings.show','meetings.store','meetings.update','meetings.destroy',
            'meetings.changeStatus','meetings.bulkDestroy','meetings.bulkUpdateStatus','meetings.export','meetings.import','meetings.stats',
            'meeting-participants.index','meeting-participants.store','meeting-participants.update','meeting-participants.destroy','meeting-participants.checkin',
            'meeting-agendas.index','meeting-agendas.store','meeting-agendas.update','meeting-agendas.destroy','meeting-agendas.reorder','meeting-agendas.setActive',
            'meeting-documents.index','meeting-documents.store','meeting-documents.update','meeting-documents.destroy',
            'meeting-conclusions.index','meeting-conclusions.store','meeting-conclusions.update','meeting-conclusions.destroy',
            'meeting-personal-notes.index','meeting-personal-notes.store','meeting-personal-notes.update','meeting-personal-notes.destroy',
            'meeting-speech-requests.index','meeting-speech-requests.store','meeting-speech-requests.approve','meeting-speech-requests.reject','meeting-speech-requests.destroy',
            'meeting-votings.index','meeting-votings.store','meeting-votings.update','meeting-votings.destroy',
            'meeting-votings.open','meeting-votings.close','meeting-votings.vote','meeting-votings.results',
        ];

        foreach ($permissions as $name) {
            $perm = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            \DB::table('model_has_permissions')->updateOrInsert([
                'permission_id' => $perm->id,
                'model_type' => 'App\\Modules\\Core\\Models\\User',
                'model_id' => 1,
                'organization_id' => 1,
            ]);
        }

        $this->command->info(count($permissions) . ' Meeting permissions assigned to user #1');
    }
}
