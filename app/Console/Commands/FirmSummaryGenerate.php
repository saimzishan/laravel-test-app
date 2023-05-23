<?php

namespace App\Console\Commands;

use App\Firm;
use App\Http\Libraries\SummaryLibrary;
use App\Http\Libraries\SummaryLibraryUserProductivity;
use Illuminate\Console\Command;

class FirmSummaryGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firm:generate-summary {--firm=} {--only=none} {--user=none}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Generate the firm's summary";

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
        $firm = Firm::find($this->option("firm"));
        if ($this->option("only") == "none") {
            $function = "";
            $firm->integrationRelation->percentage = 90;
            $firm->integrationRelation->status = "Processing";
            $firm->integrationRelation->status_message ="Your dashboard reports are now being created. Depending on the amount of data, generating time will vary.";
            $firm->push();
            (new SummaryLibrary($firm))->run($function);
            $firm->integrationRelation->status = "Processing";
            $firm->integrationRelation->percentage = 95;
            $firm->integrationRelation->status_message ="Your dashboard reports are now being created. Depending on the amount of data, generating time will vary.";
            $firm->push();
            (new SummaryLibraryUserProductivity($firm))->run("","none");
            $firm->integrationRelation->status = "Synced";
            $firm->integrationRelation->percentage = 100;
            $firm->integrationRelation->status_message =null;
            $firm->push();
        }
        elseif($this->option("only") == "userproductivity")
        {
            if($this->option("user")=="none") {
                $firm->integrationRelation->status = "Processing";
                $firm->integrationRelation->percentage = 95;
//                $firm->integrationRelation->status_message = "Generating User Productivity Summary";
                $firm->push();
                $var = new SummaryLibraryUserProductivity($firm);
                $var->run("", "none");
                $firm->integrationRelation->status = "Synced";
                $firm->integrationRelation->percentage = 100;
                $firm->integrationRelation->status_message = null;
                $firm->push();
            }
            else
            {
                $firm->integrationRelation->status = "Processing";
                $firm->integrationRelation->percentage = 95;
                $firm->integrationRelation->status_message ="Your dashboard reports are now being created. Depending on the amount of data, generating time will vary.";
                $firm->push();
                $var = new SummaryLibraryUserProductivity($firm);
                $var->run("", $this->option('user'));
                $firm->integrationRelation->status = "Synced";
                $firm->integrationRelation->percentage = 100;
                $firm->integrationRelation->status_message = null;
                $firm->push();
            }

        }

        elseif($this->option("only") == "summary")
        {
            $function = "";
            $firm->integrationRelation->percentage = 90;
            $firm->integrationRelation->status = "Processing";
            $firm->integrationRelation->status_message ="Your dashboard reports are now being created. Depending on the amount of data, generating time will vary.";
            $firm->push();
            (new SummaryLibrary($firm))->run($function);
            $firm->integrationRelation->status = "Synced";
            $firm->integrationRelation->percentage = 100;
            $firm->integrationRelation->status_message =null;
            $firm->push();
        }
        else
        {
            $function = $this->option("only");
            $firm->integrationRelation->percentage = 90;
            $firm->integrationRelation->status = "Processing";
            $firm->integrationRelation->status_message ="Your dashboard reports are now being created. Depending on the amount of data, generating time will vary.";
            $firm->push();
            (new SummaryLibrary($firm))->run($function);
            $firm->integrationRelation->status = "Synced";
            $firm->integrationRelation->percentage = 100;
            $firm->integrationRelation->status_message =null;
            $firm->push();

        }



    }
}
