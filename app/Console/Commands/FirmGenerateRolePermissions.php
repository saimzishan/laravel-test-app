<?php

namespace App\Console\Commands;

use App\Firm;
use App\FirmRole;
use App\FirmRolePermission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FirmGenerateRolePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firms:generate-role-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates Role Permissions for all firms';

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
        $firms = Firm::where("is_delete", 0)->get();
        $this->info("Total Firms: {$firms->count()}");
        $bar = $this->output->createProgressBar($firms->count());
        DB::table("firm_role_permissions")->truncate();
        foreach ($firms as $firm) {
            $roles = FirmRole::where("firm_id", $firm->id)->get();
            foreach ($roles as $role) {
                $role->generateDefaultPermissions();
            }
            $bar->advance();
        }
        $bar->finish();
        $this->info("\n");
    }
}
