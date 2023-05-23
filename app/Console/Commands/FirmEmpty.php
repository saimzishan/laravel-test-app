<?php

namespace App\Console\Commands;

use App\Firm;
use App\PPAccount;
use App\PPContact;
use App\PPContactTask;
use App\PPExpense;
use App\PPInvoice;
use App\PPMatter;
use App\PPMatterUser;
use App\PPTask;
use App\PPTaskUser;
use App\PPTimeEntry;
use App\PPUser;
use Illuminate\Console\Command;

class FirmEmpty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firm:empty {firm_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Emptying the Firm\'s Integration Data';

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
        $firm_id = $this->argument('firm_id');
        $firm = Firm::find($firm_id);
        if ($firm->integration == "practice_panther") {
            $check = PPUser::where("firm_id", $firm_id)->delete();
            $this->info("Emptying PPUsers: " . ($check ? "Success" : "Failure"));
            $check = PPAccount::where("firm_id", $firm_id)->delete();
            $this->info("Emptying PPAccounts: " . ($check ? "Success" : "Failure"));
            $check = PPContact::where("firm_id", $firm_id)->delete();
            $this->info("Emptying PPContacts: " . ($check ? "Success" : "Failure"));
            $check = PPMatter::where("firm_id", $firm_id)->delete();
            $this->info("Emptying PPMatter: " . ($check ? "Success" : "Failure"));
            $check = PPTask::where("firm_id", $firm_id)->delete();
            $this->info("Emptying PPTasks: " . ($check ? "Success" : "Failure"));
            $check = PPTimeEntry::where("firm_id", $firm_id)->delete();
            $this->info("Emptying PPTimeEntries: " . ($check ? "Success" : "Failure"));
            $check = PPInvoice::where("firm_id", $firm_id)->delete();
            $this->info("Emptying PPInvoices: " . ($check ? "Success" : "Failure"));
            $check = PPExpense::where("firm_id", $firm_id)->delete();
            $this->info("Emptying PPExpenses: " . ($check ? "Success" : "Failure"));
            PPMatterUser::where("firm_id", $firm_id)->delete();
            PPContactTask::where("firm_id", $firm_id)->delete();
            PPTaskUser::where("firm_id", $firm_id)->delete();
        }
    }
}
