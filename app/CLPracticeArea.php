<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CLPracticeArea extends Model
{
    public $timestamps = false;
    protected $table = "cl_practice_areas";
    public static function getNamefromRefID($ref_id, $firm_id) {
        $row = self::select("name")->where("ref_id", $ref_id)->where("firm_id", $firm_id)->first();
        if ($row !=null) {
            return $row->name;
        } else {
            return 0;
        }
    }
}
