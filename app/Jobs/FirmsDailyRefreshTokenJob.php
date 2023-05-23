<?php

namespace App\Jobs;

use App\Firm;
use App\FirmIntegration;
use App\Http\Libraries\ClioAPILibrary;
use App\Http\Libraries\PracticePantherAPILibrary;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FirmsDailyRefreshTokenJob implements ShouldQueue
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
        $firms = Firm::where("is_active", 1)->where("is_delete", 0)
            ->whereHas("integrationRelation", function($q){
                $q->where("status","Synced");
            })
            ->select(["id", "integration"])->get();
        foreach ($firms as $firm) {
            if ($firm->isIntegrated()) {
                if ($firm->integration == "practice_panther") {
                    (new PracticePantherAPILibrary($firm->id,false))->refreshToken();
                } else if ($firm->integration == "clio") {
                    (new ClioAPILibrary($firm->id,false))->refreshToken();
                } else {
                    continue;
                }
            }
        }
    }
}
