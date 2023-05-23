<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SummaryAOP extends Model
{
    protected $connection = "summary";
    protected $table = "summary_aops";
    protected $fillable = [
        "firm_id", "name", "clients", "matters", "revenue",
        "gross_profit_margin", "outstanding_dues"
    ];
}
