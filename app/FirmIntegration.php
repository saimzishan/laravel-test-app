<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FirmIntegration extends Model
{
    public function isConnected() {
        return (!empty($this->code) && !empty($this->access_token) && !empty($this->refresh_token)) ? true : false;
    }
}
