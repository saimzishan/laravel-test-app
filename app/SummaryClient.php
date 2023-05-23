<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SummaryClient extends Model
{
    protected $connection = "summary";
    protected $fillable = [
        "firm_id", "client_id", "client_name", "revenue", "outstanding_dues",
    ];
}
