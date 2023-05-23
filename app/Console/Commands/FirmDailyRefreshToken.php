<?php

namespace App\Console\Commands;

use App\Firm;
use App\Http\Libraries\ClioAPILibrary;
use App\Http\Libraries\PracticePantherAPILibrary;
use Illuminate\Console\Command;

class FirmDailyRefreshToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firm:dailyrefreshtoken';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'All Firms Refresh Token';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $firms = Firm::where("is_active", 1)->where("is_delete", 0)->where("id","<>","25")->where("id","<>","33")
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
