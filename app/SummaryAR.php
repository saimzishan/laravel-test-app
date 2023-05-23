<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SummaryAR extends Model
{
    protected $connection = "summary";
    protected $table = "summary_ar";
    protected $fillable = [
        "firm_id", "type", "contact_id", "contact_name", "total","outstanding","percentage_to_sale","percentage_to_outstanding"
    ];
}
