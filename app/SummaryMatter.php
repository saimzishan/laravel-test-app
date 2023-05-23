<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SummaryMatter extends Model
{
    protected $connection = "summary";
    protected $fillable = [
        "firm_id", "matter_id", "matter_name", "revenue", "outstanding_dues",
    ];
}
