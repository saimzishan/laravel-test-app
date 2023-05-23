<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        "name", "stripe_plan_id", "validity", "start_date", "end_date", "is_delete"
    ];
    //
    public function scopeNotDeleted($query) {
        return $query->where("is_delete", 0);
    }
}
