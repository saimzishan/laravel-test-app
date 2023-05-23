<?php

namespace App\Console\Commands;

use App\FirmUserOther;
use App\Mail\UserRegisterAdminOtherIntegration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEmailOfOtherFirmUserIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firm:emailAdminFirmUserOtherIntegration {id}';
    protected $firm = null;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Email to Admin About FirmUser Other Integration Registration';

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
        $this->firm = FirmUserOther::find($this->argument('id'));
        Mail::to("info@firmtrak.com")->queue(new UserRegisterAdminOtherIntegration($this->firm));
    }
}
