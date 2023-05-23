<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FirmUserOther extends Model
{
    public function getCreatedDate() {
        return date("m/d/Y (h:i A)", strtotime($this->created_at));
    }
}
