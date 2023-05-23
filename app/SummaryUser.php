<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SummaryUser extends Model
{
    protected $connection = "summary";
    protected $fillable = [
        "firm_id", "month", "user", "billable_hours", "billed_hours", "billed_hours_mom", "non_billed_hours",
        "non_billed_hours_mom","available_time", "worked_time", "billed_time", "collected_time","available_time_mom", "worked_time_mom", "collected_time_mom","open_tasks","overdue_tasks","completed_tasks","monthly_billable_target"
    ];
}
