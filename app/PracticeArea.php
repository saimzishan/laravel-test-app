<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PracticeArea extends Model
{
    public function getStatus() {
        return $this->is_active == 1 ? "Active" : "In-Active";
    }
}
