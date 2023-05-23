<?php

namespace App\Console\Commands;

use App\Firm;
use App\FirmIntegration;
use App\Jobs\FirmSyncJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class FirmsSync extends Command
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
    protected $description = 'All Firms Data Sync';

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
        foreach ($firms as $firm) {
            if ($firm->isIntegrated()) {
                $row = FirmIntegration::where("firm_id", $firm->id)->first();
                $row->status = "In-Queue";
                $row->save();
                FirmSyncJob::dispatch($firm->id)->onQueue("sync");
            }
        }
    }
}
