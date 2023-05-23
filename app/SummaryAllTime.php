<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SummaryAllTime extends Model
{
    protected $connection = "summary";
    protected $fillable = [
        "firm_id", "matters_red", "matters_yellow", "matters_green", "ar_current",
        "ar_late", "ar_delinquent", "ar_collection",
    ];
}
