<?php

namespace App\Console\Commands;

use App\Firm;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FirmExport extends Command
{

    protected $firm_id = null;
    protected $table_names = null;
    protected $columns = null;
    protected $data = null;
    protected $sql = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firm:export {firm_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Firm Integration\'s data to sql';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->table_names = [
            "pp_accounts",
            "pp_contact_task",
            "pp_contacts",
            "pp_expenses",
            "pp_invoice_line_items",
            "pp_invoices",
            "pp_matter_user",
            "pp_matters",
            "pp_task_user",
            "pp_tasks",
            "pp_time_entries",
            "pp_users",
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->firm_id = $this->argument("firm_id");
        $integration = Firm::find($this->firm_id)->integration;
        $this->info("Firm ID: {$this->firm_id}\nFirm Integration: {$integration}");
        if ($integration == "practice_panther") {
            $this->doStep1();
            $this->doStep2();
            $this->doStep3();
            $this->doStep4();
        } else {
            $this->info("Firm Integration ({$integration}) Not Supported Right Now");
        }
        unset($integration);
    }

    private function doStep1() {
        $this->info("Step 1: Pending   | Gathering Table Structure");
        foreach ($this->table_names as $k => $v) {
            $columns = [];
            $temp = DB::select("show columns from {$v} where field != 'id'");
            foreach ($temp as $row) {
                $columns[] = $row->Field;
            }
            $this->columns[$v] = $columns;
            unset($columns);
            unset($temp);
        }
        $this->info("Step 1: Completed | Gathering Table Structure");
    }

    private function doStep2() {
        $this->info("Step 2: Pending   | Gathering Table Data");
        foreach ($this->table_names as $k => $v) {
            $columns = implode(", ", $this->columns[$v]);
            $this->data[$v] = DB::select("select {$columns} from {$v} where firm_id = '{$this->firm_id}'");
            unset($columns);
        }
        $this->info("Step 2: Completed | Gathering Table Data");
    }

    private function doStep3() {
        $this->info("Step 3: Pending   | Converting Data to SQL");
        foreach ($this->data as $table_name => $data) {
            foreach ($data as $key => $row) {
                $columns = '';
                $values = '';
                $i = 1;
                foreach ($row as $k => $v) {
                    if ($i == count((array) $row)) {
                        if (($k == "created_at" || $k == "updated_at" || $k == "due_date" || $k == "open_date" || $k == "close_date" || $k == "date_of_joining" || $k == "date") && empty($v)) {
                            $values .= "null";
                        } else {
                            $values .= "'" . addslashes($v) . "'";
                        }
                        $columns .= "`{$k}`";
                    } else {
                        if (($k == "created_at" || $k == "updated_at" || $k == "due_date" || $k == "open_date" || $k == "close_date" || $k == "date_of_joining" || $k == "date") && empty($v)) {
                            $values .= "null, ";
                        } else {
                            $values .= "'" . addslashes($v) . "', ";
                        }
                        $columns .= "`{$k}`, ";
                    }
                    $i++;
                }
                $this->sql[] = "INSERT INTO {$table_name} ({$columns}) VALUES ({$values});";
                unset($columns);
                unset($values);
                unset($i);
            }
        }
        $this->info("Step 3: Completed | Converting Data to SQL");
    }

    private function doStep4() {
        $this->info("Step 4: Pending   | Exporting SQL File");
        $name = time() . "_firm_{$this->firm_id}.sql";
        Storage::disk("exports")->put($name, implode("\n", $this->sql));
        $this->info("Step 4: Completed | Exporting SQL File");
        $this->comment("Exported File: ".base_path("storage/exports/{$name}"));
        unset($name);
    }
}
