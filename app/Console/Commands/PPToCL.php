<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Libraries\PPToCLfirmTRAKLibrary;

class PPToCL extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firm:migrate {firm_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrating the Firm\'s Data';

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
        $lib = new PPToCLfirmTRAKLibrary($firm_id);
        $output = "-== Firm ID: {$firm_id} at ".date("d-M-Y (h:i A)")." ==-";
        $output .= "\r\nStep 1: " . ($lib->syncUsers() ? "Success" : "Failure");
        $output .= "\r\nStep 2: " . ($lib->syncContacts() ? "Success" : "Failure");
        $output .= "\r\nStep 3: " . ($lib->syncMatters() ? "Success" : "Failure");
        $output .= "\r\nStep 4: " . ($lib->syncTasks() ? "Success" : "Failure");
        $output .= "\r\nStep 5: " . ($lib->syncTimeEntries() ? "Success" : "Failure");
        $output .= "\r\nStep 6: " . ($lib->syncInvoices() ? "Success" : "Failure");
        $output .= "-== END ==-\r\n";
        $this->info($output);
    }
}
