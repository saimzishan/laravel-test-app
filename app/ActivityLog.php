<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ["firm_user_id", "desc"];

    public function getDateTime() {
        return date("M d, Y (h:i A)", strtotime($this->created_at));
    }

}
