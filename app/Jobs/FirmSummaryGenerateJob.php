<?php

namespace App\Jobs;

use App\Firm;
use App\Http\Libraries\SummaryLibrary;
use App\Http\Libraries\SummaryLibraryUserProductivity;
use App\Mail\FirmFirstTimeSync;
use App\Mail\FirmSubscriptionCancellation;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class FirmSummaryGenerateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $firm_id = null;
    private $firstTime = null;
    private $function = "";
    private $user_id = "none";
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($firm_id, $function="", $firstTime=false,$user_id="none")
    {
        $this->firm_id = $firm_id;
        $this->function = $function;
        $this->firstTime = $firstTime;
        $this->user_id = $user_id;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->user_id!= "none" or $this->user_id== "all")
        {
            $firm = Firm::find($this->firm_id);
            $firm->integrationRelation->status = "Processing";
            $firm->integrationRelation->status_message ="Your dashboard reports are now being created. Depending on the amount of data, generating time will vary.";
            $firm->push();
            $var = new SummaryLibraryUserProductivity($firm);
            $var->run("", $this->user_id);
            $firm->integrationRelation->status = "Synced";
            $firm->integrationRelation->status_message = null;
            $firm->push();
        }
        else
        {
            $firm = Firm::find($this->firm_id);
            $firm->integrationRelation->status = "Processing";
            $firm->integrationRelation->status_message ="Your dashboard reports are now being created. Depending on the amount of data, generating time will vary.";
            $firm->push();
            (new SummaryLibrary($firm))->run($this->function);
            $firm->integrationRelation->status = "Synced";
            $firm->integrationRelation->status_message = null;
            $firm->push();
        }
        if ($this->firstTime) {
            foreach ($firm->getFirmAdmins() as $admin) {
                Mail::to($admin->email)->queue(new FirmFirstTimeSync($admin));
            }
        }
    }
}
