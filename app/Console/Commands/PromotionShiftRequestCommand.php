<?php

namespace App\Console\Commands;

use App\PromotionShiftRequest;
use App\Firm;
use Illuminate\Console\Command;

class PromotionShiftRequestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotion:check-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Completes all available promotion shift requests';

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
        $data = PromotionShiftRequest::where("is_completed", false)
            ->where("end_date", date("Y-m-d", strtotime("-1 day")))
            ->get();
        foreach ($data as $row) {
            $firm = Firm::find($row->firm_id);
            $firm->subscription('main')->noProrate()->swap($row->subscription_to);
            $row->is_completed = true;
            $row->push();
        }
    }
}
