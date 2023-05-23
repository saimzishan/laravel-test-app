<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CLCredit extends Model
{
    public $timestamps = false;
    protected $table = "cl_credits";

    public static function calcCredit($monthYear, $year = "month", $firm_id=0) {
        $data = self::where("firm_id", $firm_id);
        if ($monthYear != "") {
            if ($year=="year") {
                $data = $data->whereRaw("date >= '{$monthYear->from}' and date <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $month = explode('-',$monthYear);
               // dd($month);
                $data = $data->whereRaw("MONTH(date) = {$month[1]} AND YEAR(date)= {$month[0]}");
            } elseif ($year=="today") {
                $data = $data->whereRaw("date = '{$monthYear}'");
            }
        }
       // dd($data->toSql());
        $temp = round($data->sum("amount"), 2);

        return $temp;
    }
}
