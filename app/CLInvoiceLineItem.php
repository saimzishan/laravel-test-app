<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CLInvoiceLineItem extends Model
{
    protected $table = "cl_invoice_line_items";
    public $timestamps = false;

    public function user() {
        return $this->belongsTo('App\CLUser', "clio_user_id","ref_id");
    }
    public function matter() {
        return $this->belongsTo("App\CLMatter", "clio_matter_id","ref_id");
    }
    public function timeEntry() {
        return $this->belongsTo("App\CLTimeEntry", "clio_time_entry_id","ref_id");
    }
    public function invoice() {
        return $this->belongsTo("App\CLInvoice", "clio_invoice_id","ref_id");
    }

    public static function getTotalBilledHours($month="", $year = false, $firm_id = 0, $user="all") {
        $data = self::whereHas('timeEntry')
            ->whereHas('invoice', function($q){
            $q->where("state","<>","void");
            $q->where("state","<>","deleted");
            $q->where("state","<>","draft");
        })->where("kind", "Service")->where('firm_id', $firm_id);
        if ($month !== "") {
            if ($year=="year") {
                $data = $data->whereRaw("date >= '{$month->from}' and date <= '{$month->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(date, 7) = '{$month}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(date, 10) = '{$month}'");
            }
        }
        if ($user != "all") {
            $data->whereHas('timeEntry', function($q) use ($user){
                $q->where("clio_user_id",$user);
            });
        }
        return $data->sum("quantity");
    }

    public static function calcTotalBilledHoursYearWise($monthYear, $firm_id = 0, $user="all") {
        $data = 0;
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data += self::getTotalBilledHours($i->format("Y-m"), "month", $firm_id, $user);
        }
        return round($data / 12, 2);
    }
}
