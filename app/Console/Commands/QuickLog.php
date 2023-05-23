<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class QuickLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quick:log {--m=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quickly log the text given as parameter';

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
        $msg = $this->option("m");
        $timestamp = date("d-m-y (H:i:s)");
        Log::channel("general")->info("A Message Logged at ({$timestamp}) '{$msg}'");
    }
}
