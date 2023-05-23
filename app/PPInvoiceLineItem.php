<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PPInvoiceLineItem extends Model
{
    protected $table = "pp_invoice_line_items";
    public $timestamps = false;
    public function user()
    {
        return $this->belongsTo('App\PPUser', 'billed_by', 'display_name');
    }
    public function invoice()
    {
        return $this->belongsTo('App\PPInvoice', 'pp_invoice_id','id');
    }

    public static function getTotalBilledHours($month="", $year = false, $firm_id = 0, $user="all") {
        $data = self::whereHas('user', function($q){
            $q->where('can_be_calculated', true);
        })->where("rate", "<>", 0)->where('firm_id', $firm_id)->where('type', 'time_entries');
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
            $data->where("billed_by", PPUser::find($user)->display_name);
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
    public static function calcRevenueByUser($monthYear, $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null) {
        $data = self::where("firm_id", $firm_id)
            ->whereHas('invoice',function($q){
                $q->where("invoice_type", 'Sale');
            })
            ->where('total', ">", 0)
            ->select(["total"]);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("date >= '{$monthYear->from}' and date <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(date, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(date, 10) = '{$monthYear}'");
            }
        }
         if ($user != "all") {
             $data = $data->where("billed_by", PPUser::find($user)->display_name);

         }
        // if ($mt != "all") {
        //     $data = $data->whereHas("matter", function($q)use($mt){
        //         $q->where("matter_type", $mt);
        //     });
        // }
        // if ($mid != null) {
        //     $data = $data->whereHas("matter", function($q)use($mid){
        //         $q->where("id", $mid);
        //     });
        // }

        return round($data->sum("total"), 2);
    }
}
