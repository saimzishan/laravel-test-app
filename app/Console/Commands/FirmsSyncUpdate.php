<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Firm;
use App\FirmIntegration;
use App\Jobs\FirmSyncUpdateJob;
use Illuminate\Console\Command;

class FirmsSyncUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firm:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'All Firms Data Sync Update';

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
        $firms = Firm::where("is_active", 1)->where("is_delete", 0)->select(["id", "integration"])->get();
        $firms = $firms->sortBy(function($firms){
            return $firms->getIntegrationUsers->count();
        });
        foreach ($firms as $firm) {
            if ($firm->isIntegrated() and $firm->isSynced()) {
                if($firm->id != 25 and $firm->id != 33) {
                    $row = FirmIntegration::where("firm_id", $firm->id)->first();
                    $row->status = "In-Queue";
                    $row->status_message = "Refresh is in progress. Refresh times vary depending on the amount of data being imported.";
                    $row->save();
                    FirmSyncUpdateJob::dispatch($firm->id)->onQueue("syncupdate");
                }
            }
        }

    }
}
