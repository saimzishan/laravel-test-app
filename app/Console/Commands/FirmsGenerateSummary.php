<?php

namespace App\Console\Commands;

use App\Firm;
use App\Jobs\FirmSummaryGenerateJob;
use Illuminate\Console\Command;

class FirmsGenerateSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firms:generate-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Summary For All Active Firms';

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
                FirmSummaryGenerateJob::dispatch($firm->id)->onQueue("summary");
            }
        }
    }
}
