<?php

namespace App\Jobs;

use App\Firm;
use App\FirmIntegration;
use App\Http\Libraries\PracticePantherAPILibrary;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Exception;

class FirmSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $firm_id = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($firm_id)
    {
        $this->firm_id = $firm_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /*\Illuminate\Support\Facades\Storage::disk("uploads")->put(time().'.txt', $this->firm_id);*/
        Artisan::call("firm:sync", ["firm_id"=>$this->firm_id, "--type"=>"automatic"]);
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        $row = FirmIntegration::where("firm_id", $this->firm_id)->first();
        $row->status = "Error";
        $row->save();
    }
}
