<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SummaryWrittenOffByEmployee extends Model
{
    protected $connection = "summary";
    protected $fillable = [
        "firm_id", "employee_name", "total_hours", "written_off_hours","manually_written_off_hours", "billed_hours", "total_hours_amount", "written_off_amount","manually_written_off_hours_amount",
        "billed_amount","post_billed_credits_amount","differences"];
}
