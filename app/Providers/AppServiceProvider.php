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
    }
}
