<?php

namespace App\Listeners;

use App\Models\Setting;
use CyrildeWit\EloquentViewable\Events\ViewRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateUserEarningsOnPasteViewed
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\CyrildeWitEloquentViewableEventsViewed  $event
     * @return void
     */
    public function handle(ViewRecorded $event)
    {
        $paste = $event->view->viewable;
        $user = $paste->user;
        $setting = Setting::where('key', 'costPerView')->first();
        $costPerView = $setting ? floatval($setting->value) : 0.002;
        if ($user) {
            $user->increment('total_views');
            if ($user->allowAffiliate)
                $user->increment('earnings', $costPerView); // $2 per 1000 view
        }
    }
}
