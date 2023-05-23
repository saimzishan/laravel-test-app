<?php

namespace App\Console\Commands;

use App\Firm;
use App\Http\Libraries\ClioAPILibrary;
use App\Jobs\FirmSummaryGenerateJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FirmSyncMatterType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firm:syncmattertype {firm_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncing the Firm\'s Data';

    protected $firm = null;
    protected $steps = [];
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
        if ($this->firm->integration == "clio") {
            $this->syncCL();
            $output = $this->generateOutput();
        } else {
            $output = "No Integration Matched of Firm ID: {$this->firm->id}";
        }
    }
    private function syncCL () {
        $lib = new ClioAPILibrary($this->firm->id, "false");
        if (($this->steps[0] = $lib->refreshToken()) == true) {
           $this->steps[] = $lib->syncMattersPracticeAres();
        }
    }

    private function generateOutput () {
        $output = "-== Firm ID: {$this->firm->id} ({$this->firm->integration}) at ".date("d-M-Y (h:i A)")." ==-";
            foreach ($this->steps as $k=>$v) {
                $output .= "\r\nStep " . ($k+1) . ": " . ($v ? "Success" : "Failure");
            }

        $output .= "\r\n-== END ==-\r\n";
        return $output;
    }
}
