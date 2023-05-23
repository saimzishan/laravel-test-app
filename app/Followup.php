<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Followup extends Model
{
    public function scopeNotdeleted($query){
        return $query->where("is_delete", 0);
    }
    public function getDate(){
        if ($this->date) {
            return date("m-d-Y", strtotime($this->date));
        } else {
            return "";
        }
    }
    public function getReplyDate(){
        if ($this->reply_date) {
            return date("m-d-Y", strtotime($this->reply_date));
        } else {
            return "";
        }
    }
    public function getType(){
        return ucwords(str_replace("_", " ", $this->type));
    }
}
