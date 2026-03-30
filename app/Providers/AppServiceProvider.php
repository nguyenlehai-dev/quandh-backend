<?php

namespace App\Providers;

use App\Modules\Meeting\Models\MeetingPersonalNote;
use App\Modules\Meeting\Policies\MeetingPersonalNotePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Meeting module policies
        Gate::policy(MeetingPersonalNote::class, MeetingPersonalNotePolicy::class);

        // Bypass permission checks for Super Admin globally (regardless of current team scope)
        Gate::before(function ($user, $ability) {
            static $superAdminCache = [];
            
            if (!array_key_exists($user->id, $superAdminCache)) {
                $superAdminCache[$user->id] = \Illuminate\Support\Facades\DB::table(config('permission.table_names.model_has_roles'))
                    ->where('model_id', $user->id)
                    ->where('model_type', get_class($user))
                    ->whereIn('role_id', function ($query) {
                        $query->select('id')->from(config('permission.table_names.roles'))
                            ->whereIn('name', ['Quản trị hệ thống', 'Super Admin']);
                    })
                    ->exists();
            }

            return $superAdminCache[$user->id] ? true : null;
        });
    }
}
