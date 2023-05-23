<?php

namespace App\Jobs;

use App\Firm;
use App\FirmUser;
use App\Mail\NotifyFirmTrialDaysLeft;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckAndNotifyFirmTrialDaysLeft implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $firms = Firm::where("is_delete", 0)->where("is_active", 1)->get();
        foreach ($firms as $firm) {
            if ($firm->isTrial() && !$firm->isTrialExpired() && $firm->getTrialDaysLeft() == 2) {
                foreach ($firm->getFirmAdmins() as $admin) {
                    \Mail::to($admin->email)->queue(new NotifyFirmTrialDaysLeft($admin));
                }
            }
        }
    }
}
