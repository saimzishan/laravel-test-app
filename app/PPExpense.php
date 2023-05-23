<?php

namespace App;

use App\Http\Libraries\HelperLibrary;
use Illuminate\Database\Eloquent\Model;

class PPExpense extends Model
{
    public $timestamps = false;
    protected $table = "pp_expenses";

    public function user() {
        return $this->belongsTo('App\PPUser', "billed_by_user_id");
    }
    public function matter() {
        return $this->belongsTo("App\PPMatter", "pp_matter_id");
    }

    public static function calcExpense($monthYear, $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null, $package=null) {
        $data = self::where("firm_id", $firm_id);
        $users = PPUser::where("firm_id", $firm_id)
            ->where("can_be_calculated", true)
            ->select(["hours_per_week", "cost_per_hour","display_name","id"]);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
            }
        }
        if ($user != "all") {
            $data = $data->where("billed_by_user_id", $user);
        }
        if ($mt != "all") {
            $data = $data->whereHas("matter", function($q)use($mt){
                $q->where("matter_type", $mt);
            });
        }
        if ($mid != null) {
            $data = $data->whereHas("matter", function($q)use($mid){
                $q->where("id", $mid);
            });
        }
        $final = $data->sum("amount");
        foreach ($users->get() as $v) {
            $u = PPTimeEntry::getTotalBillableHours($monthYear,$year,$firm_id,$v->id);
            $final += $u * $v->cost_per_hour;
        }
        return round($final, 2);
    }

    public static function calcExpenseYearWise($monthYear, $firm_id=0, $user="all", $mt="all") {
        $data = collect([]);
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data->push(self::calcExpense($i->format("Y-m"), "month", $firm_id, $user, $mt));
        }
        return $data;
    }

}
