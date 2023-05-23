<?php

namespace App\Console\Commands;

use App\Firm;
use App\FirmIntegration;
use App\Http\Libraries\ClioAPILibrary;
use App\Http\Libraries\PracticePantherAPILibrary;
use App\Http\Libraries\StripeLibrary;
use App\Jobs\FirmSummaryGenerateJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FirmSyncUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firm:syncUpdate {firm_id} {--type=manual} {--debuging=false} {--only=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updating the Firm\'s Data';

    /**
     * Custom Variables
     */
    protected $firm = null;
    protected $debugging = false;
    protected $error = false;
    protected $only = "all";
    protected $steps = [];
    protected $firstTime = false;
    protected $last_syncdate = null;
    protected $summaryGenerate = true;


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
        if ($this->option('debuging')) {
            $this->debugging = true;
        } else {
            $this->debugging = false;
        }
        $this->only = $this->option("only");
        $this->firm = Firm::find($this->argument('firm_id'));
        $this->firstTime = $this->firm->integrationRelation->status == "Disconnected" ? true : false;
        $this->firm->integrationRelation->status = "Syncing";
        $this->firm->integrationRelation->percentage = 0;
        $this->last_syncdate = $this->firm->integrationRelation->updated_at;
        $this->firm->push();
        $stripe = new StripeLibrary($this->firm->id);
        if ($this->firm->integration == "practice_panther") {
            $this->syncPP();
            $stripe->updateUserQuantity();
            $output = $this->generateOutput();
        } elseif ($this->firm->integration == "clio") {
            $this->syncCL();
            $stripe->updateUserQuantity();
            $output = $this->generateOutput();
        } else {
            $output = "No Integration Matched of Firm ID: {$this->firm->id}";
        }
        if ($this->option('type') == "manual") {
            $this->info($output);
        }
        Log::channel("task_log")->info($output);
    }

    private function syncPP () {
        FirmIntegration::where("firm_id",$this->firm->id)->update(["status_message"=>"Refresh is in progress. Refresh times vary depending on the amount of data being imported."]);
        $lib = new PracticePantherAPILibrary($this->firm->id, $this->debugging);
        if (($this->steps[0] = $lib->refreshToken()) == true) {
            $this->firm->integrationRelation->percentage = 10;
            $this->firm->push();
            if ($this->only == "all") {
                $this->steps[] = $lib->updateUsers();
                $this->firm->integrationRelation->percentage = 20;
                $this->firm->push();
                $this->steps[] = $lib->updateAccounts();
                $this->firm->integrationRelation->percentage = 30;
                $this->firm->push();
                $this->steps[] = $lib->updateContacts();
                $this->firm->integrationRelation->percentage = 40;
                $this->firm->push();
                $this->steps[] = $lib->updateMatters();
                $this->firm->integrationRelation->percentage = 50;
                $this->firm->push();
                $this->steps[] = $lib->updateTasks();
                $this->firm->integrationRelation->percentage = 60;
                $this->firm->push();
                $this->steps[] = $lib->updateTimeEntries();
                $this->firm->integrationRelation->percentage = 70;
                $this->firm->push();
                $this->steps[] = $lib->updateInvoices();
                $this->firm->integrationRelation->percentage = 80;
                $this->firm->push();
                $this->steps[] = $lib->updateExpenses();
                $this->firm->integrationRelation->percentage = 90;
                $this->firm->integrationRelation->last_sync = Carbon::now()->format("Y-m-d H:i:s");
                $this->firm->push();
            } else {
                $this->summaryGenerate = false;
                $this->firm->integrationRelation->percentage = 80;
                $this->steps[] = $lib->{$this->only}();
                $this->firm->integrationRelation->last_sync = Carbon::now()->format("Y-m-d H:i:s");
                $this->firm->push();
            }
        } else {
            $this->error = true;
            $this->firm->integrationRelation->percentage = 0;
            $this->firm->integrationRelation->last_sync = Carbon::now()->format("Y-m-d H:i:s");
            $this->firm->push();

        }
    }

    private function syncCL () {
        FirmIntegration::where("firm_id",$this->firm->id)->update(["status_message"=>"Daily Sync is in progress. Depending on the size of your law firm, the timing could vary."]);
        $lib = new ClioAPILibrary($this->firm->id, $this->debugging);
        if (($this->steps[0] = $lib->refreshToken()) == true) {
            $this->firm->integrationRelation->percentage = 10;
            $this->firm->push();
            if ($this->only == "all") {
                $this->steps[] = $lib->updateUsers();
                $this->firm->integrationRelation->percentage = 20;
                $this->firm->push();
                $this->steps[] = $lib->updateContacts();
                $this->firm->integrationRelation->percentage = 30;
                $this->firm->push();
                $this->steps[] = $lib->updateMatters();
                $this->firm->integrationRelation->percentage = 40;
                $this->firm->push();
                $this->steps[] = $lib->updateTasks();
                $this->firm->integrationRelation->percentage = 50;
                $this->firm->push();
                $this->steps[] = $lib->updateTimeEntries();
                $this->firm->integrationRelation->percentage = 60;
                $this->firm->push();
                $this->steps[] = $lib->updateInvoices();
                $this->firm->integrationRelation->percentage = 70;
                $this->firm->push();
                $this->steps[] = $lib->syncInvoiceLineItems();
                $this->firm->integrationRelation->percentage = 75;
                $this->firm->push();
                $this->steps[] = $lib->syncCredits();
                $this->firm->integrationRelation->percentage = 80;
                $this->firm->integrationRelation->last_sync = Carbon::now()->format("Y-m-d H:i:s");
                $this->firm->push();
            } else {
                $this->summaryGenerate = false;
                $this->firm->integrationRelation->percentage = 80;
                $this->steps[] = $lib->{$this->only}();
                $this->firm->integrationRelation->last_sync = Carbon::now()->format("Y-m-d H:i:s");
                $this->firm->push();
            }
        } else {
            $this->error = true;
            $this->firm->integrationRelation->percentage = 0;
            $this->firm->integrationRelation->last_sync = Carbon::now()->format("Y-m-d H:i:s");
            $this->firm->push();
        }
    }

    private function generateOutput () {
        $output = "-== Firm ID: {$this->firm->id} ({$this->firm->integration}) at ".date("d-M-Y (h:i A)")." ==-";
        if (!$this->error) {
            foreach ($this->steps as $k=>$v) {
                $output .= "\r\nStep " . ($k+1) . ": " . ($v ? "Success" : "Failure");
            }
            if ($this->summaryGenerate){
                $this->firm->integrationRelation->percentage = 90;
                FirmIntegration::where("firm_id",$this->firm->id)->update(["status_message"=>"Your Daily dashboard reports are now being created. Depending on the amount of data, generating time will vary."]);
                $this->firm->push();
                FirmSummaryGenerateJob::dispatch($this->firm->id, "", $this->firstTime)->onQueue("summary");
                FirmSummaryGenerateJob::dispatch($this->firm->id, "","","all")->onQueue("summary");
                //FirmIntegration::where("firm_id",$this->firm->id)->update(["status_message"=>"Daily Sync Completed Successfully.","percentage"=>100]);
                //sleep(10);
                //FirmIntegration::where("firm_id",$this->firm->id)->update(["status_message"=>null,"percentage"=>100]);
            } else {
                $this->firm->integrationRelation->status = 'Synced';
                $this->firm->integrationRelation->status_message = null;
                $this->firm->integrationRelation->percentage = 85;
                $this->firm->push();
            }
        } else {
            $output .= "\r\nStep 1: Failure (Re-connect required)";
            $this->firm->integrationRelation->status = "Re-Authorize";
            $this->firm->integrationRelation->percentage = 0;
            $this->firm->push();
        }
        $output .= "\r\n-== END ==-\r\n";
        return $output;
    }
}
