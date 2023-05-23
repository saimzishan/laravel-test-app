<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SummaryMatterTracker extends Model
{
    protected $connection = "summary";
    protected $fillable = [
        "firm_id", "matter_id", "matter_name", "activities", "time_entries",
        "invoices", "days_file_open", "created_date", "created_date_raw", "type"
    ];
}
