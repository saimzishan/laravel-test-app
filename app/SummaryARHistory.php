<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SummaryARHistory extends Model
{
    protected $connection = "summary";
    protected $table = "summary_ar_history";
    protected $fillable = [
        "firm_id", "ar_current", "ar_late", "ar_delinquent", "ar_collection",
        "ar_total"
    ];
}
