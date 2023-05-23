<?php

namespace App\Console\Commands;

use App\Firm;
use App\SummaryAllTime;
use App\SummaryAOP;
use App\SummaryAR;
use App\SummaryClient;
use App\SummaryMatter;
use App\SummaryMatterTracker;
use App\SummaryMonth;
use App\SummaryUser;
use App\SummaryWrittenOffByClient;
use App\SummaryWrittenOffByEmployee;
use Illuminate\Console\Command;

class FirmDeleteSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firm:deleteSummary {firm_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Firm Delete Summary Table';

    protected $firm = null;
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
        $this->firm = Firm::find($this->argument('firm_id'));
        Firm::doDeleteSummary($this->firm->id);
    }
}
