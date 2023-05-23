<?php

namespace App;

use App\PPExpense;
use App\Traits\TimelineTrait;
use Illuminate\Database\Eloquent\Model;

class PPInvoice extends Model
{
    use TimelineTrait;

    public $timestamps = false;
    protected $table = "pp_invoices";

    public function matter() {
        return $this->belongsTo("App\PPMatter", "pp_matter_id");
    }
    public function account () {
        return $this->belongsTo("App\PPAccount", "pp_account_id");
    }
    public function lineItems()
    {
        return $this->hasMany('App\PPInvoiceLineItem', 'pp_invoice_id');
    }

    public function getIssueDate() {
        return date("m-d-Y", strtotime($this->issue_date));
    }

    public function getContactDisplayName() {
        if ($this->account != null) {
            return $this->account->getDisplayName();
        } else {
            return "-";
        }
    }
    public static function getIDfromRefID($ref_id, $firm_id) {
        $row = self::select("id")->where("ref_id", $ref_id)->where("firm_id", $firm_id)->first();
        if ($row != null) {
            return $row->id;
        } else {
            return 0;
        }
    }

    public function getMatterName() {
        if ($this->matter != null) {
            return $this->matter->getName();
        } else {
            return "-";
        }
    }

    public function getStatus() {
        $definitions = Definition::getInvoicesDefinitions();
        if ($this->total_outstanding == 0) {
            return "Paid";
        } else {
            $cd = new \DateTime();
            $id = new \DateTime(substr($this->issue_date, 0, 10));
            $diff = $id->diff($cd)->format("%a");
            if ($diff >= $definitions->current_from && $diff <= $definitions->current_to) {
                return "Current";
            } elseif ($diff >= $definitions->late_from && $diff <= $definitions->late_to) {
                return "Late";
            } elseif ($diff >= $definitions->delinquent_from && $diff <= $definitions->delinquent_to) {
                return "Delinquent";
            } elseif ($diff >= $definitions->collection_from && $diff <= $definitions->collection_to) {
                return "Collection";
            }
        }
    }

    public static function calcCollectionRaw($monthYear = "", $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null) {
        $data = self::where('firm_id',$firm_id)->where('invoice_type','Sale');
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("issue_date >= '{$monthYear->from}' and issue_date <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(issue_date, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(issue_date, 10) = '{$monthYear}'");
            }
        }
//        $data = $data->whereHas("lineItems", function($q){
//            $q->where('type', "time_entries");
//        });
        if ($user != "all") {
            $data = $data->whereHas("lineItems", function($q)use($user){
                $q->where("billed_by", PPUser::find($user)->display_name);
            });
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
        return $data;
    }

    public static function calcCollection($monthYear = "", $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null) {
        $countT = 0;
        $countTP = 0;
        $data = self::calcCollectionRaw($monthYear, $year, $firm_id, $user, $mt, $mid)->get();
        foreach ($data as $val) {
            $countT = $countT + $val->total;
            $countTP = $countTP + $val->total_paid;
        }
        $final = $countT == 0 ? 0 : ($countTP / $countT) * 100;
        return round($final, 2);
    }
    public static function calcCollectionHours($monthYear = "", $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null) {
        $countTP = 0;
        $hours = 0;
        $total = 0;
        $data = self::calcCollectionRaw($monthYear, $year, $firm_id, $user, $mt, $mid)->get();
        foreach ($data as $val) {

            foreach($val->lineItems() as $lineval)
            {
                $hours = $hours + $lineval->quantity;
                $total = total + $lineval->total;
            }
            $countTP = $countTP + $val->total_paid;
        }
        if ($hours != 0) {
            $countR = $total/$hours;
        } else {
            $countR = 0;
        }
        $final = $countR == 0 ? 0 : ($countTP / $countR);
        return round($final, 2);
    }

    public static function calcCollectionSimple($monthYear = "", $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null) {
        $data = self::calcCollectionRaw($monthYear, $year, $firm_id, $user, $mt, $mid);
        if ($user == "all") {
            return $data->sum("total_paid");
        } else {
            $collection = 0;
            foreach ($data->get() as $v) {
                if ($v->total_paid != 0) {
                    $perc = ($v->lineItems->sum("total") / $v->total_paid) * 100;
                    $collection += ($v->total_paid * $perc) / 100;
                }
            }
            return $collection;
        }
    }

    public static function calcCollectionSimpleYearWise($monthYear, $firm_id=0, $user="all", $mt="all") {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data[] = self::calcCollectionSimple($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return $data;
    }

    public static function calcCollectionYearWise($monthYear, $firm_id=0, $user="all", $mt="all") {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data[] = self::calcCollection($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return $data;
    }

    public static function calcCollectionRawYearWise($monthYear, $firm_id=0, $user="all", $mt="all") {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data[] = self::calcCollectionRaw($i->format("Y-m"), "month", $firm_id, $user, $mt)->sum("total_paid");
        }
        return $data;
    }

    public static function calcCollectionYearWiseAverage($monthYear, $firm_id=0, $user="all", $mt="all") {
        $data = 0;
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data += self::calcCollection($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return round($data / 12, 2);
    }
    public static function calcCollectionYearWiseAverageHours($monthYear, $firm_id=0, $user="all", $mt="all") {
        $data = 0;
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data += self::calcCollectionHours($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return round($data / 12, 2);
    }

    public static function calcRevenue($monthYear, $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null) {
        $data = self::where("firm_id", $firm_id)
            ->where('invoice_type','Sale')
            ->where('total', ">", 0)
            ->select(["total"]);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("issue_date >= '{$monthYear->from}' and issue_date <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(issue_date, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(issue_date, 10) = '{$monthYear}'");
            }
        }
//         if ($user != "all") {
//             $data = $data->whereHas("lineItems", function($q)use($user){
//                 $q->where("billed_by_user_id", $user);
//             });
//         }
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
    public static function calcCredit($monthYear, $year = "month", $firm_id=0) {
        $data = self::where("firm_id", $firm_id)
            ->where('invoice_type','Credit');
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("issue_date >= '{$monthYear->from}' and issue_date <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(issue_date, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(issue_date, 10) = '{$monthYear}'");
            }
        }
        $temp = round($data->select(["total"])->sum("total"), 2);
        if ($temp < 0){
            $temp = $temp * -1;
            return $temp;
        } else {
            return $temp;
        }
    }
    public static function calcRefund($monthYear, $year = "month", $firm_id=0) {
        $data = self::where("firm_id", $firm_id)
            ->where('invoice_type','Refund');
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("issue_date >= '{$monthYear->from}' and issue_date <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(issue_date, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(issue_date, 10) = '{$monthYear}'");
            }
        }
        $temp = round($data->select(["total"])->sum("total"), 2);
        if ($temp < 0){
            $temp = $temp * -1;
            return $temp;
        } else {
            return $temp;
        }
    }

    public static function calcRevenueLineItems($monthYear, $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null) {
        $data = self::where("firm_id", $firm_id)
            ->where('invoice_type','Sale');
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("issue_date >= '{$monthYear->from}' and issue_date <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(issue_date, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(issue_date, 10) = '{$monthYear}'");
            }
        }
        $data = $data->whereHas("lineItems", function($q){
            $q->where('type', "time_entries");
        });
        $data = $data->whereHas("lineItems.user", function($q){
            $q->where('can_be_calculated', true);
        });
        if ($user != "all") {
            $data = $data->whereHas("lineItems.user", function($q)use($user, $firm_id){
                $q->where("id", $user);
                $q->where("firm_id", $firm_id);
            });
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
        $sum = 0;
        foreach ($data->get() as $v) {
            $sum += $v->lineItems->sum("total");
        }
        return round($sum, 2);
    }

    public static function calcRevenueYearWise($monthYear, $firm_id=0, $user="all", $mt="all") {
        $data = PPInvoice::where("firm_id", $firm_id)
            ->where(function ($q) use ($monthYear) {
                $q->where("issue_date", ">=", $monthYear->from);
                $q->where("issue_date", "<=", $monthYear->to);
            })
            ->where('invoice_type','Sale')
            ->where('total', ">", 0)
            ->groupBy("month")
            ->selectRaw("round(sum(total) / 1000, 1) as total, DATE_FORMAT(issue_date, \"%Y-%m\") as month")
            ->get()
            ->map(function ($row) {
                return $row->total;
            });
//        $data = [];
//        $begin = new \DateTime(substr($monthYear->from, 0, 10));
//        $end = new \DateTime(substr($monthYear->to, 0, 10));
//        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
//            $data[] = self::calcRevenue($i->format("Y-m"), "month", $firm_id, $user, $mt);
//        }
        return $data;
    }

    public static function calcGrossProfit($monthYear, $year = "month", $firm_id=0, $user="all", $mt="all") {
    
        $revenue = self::calcRevenue($monthYear, "month", $firm_id, $user, $mt);
        $expense =  PPExpense::calcExpense($monthYear, "month", $firm_id, $user, $mt);
        $result = $revenue - $expense;
        return round($result, 2);
    }

    public static function calcGrossProfitYearWise($monthYear, $firm_id=0, $user="all", $mt="all") {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $revenue = self::calcRevenue($i->format("Y-m"), "month", $firm_id, $user, $mt);
            $expense = PPExpense::calcExpense($i->format("Y-m"), "month", $firm_id, $user, $mt);
            $result = $revenue - $expense;
            $data[] = round($result, 2);
        }
        return $data;
    }

    public function getTimelineEntry () {
        return [
            "id" => $this->id,
            "icon" => $this->getTimelineEntryIcon("invoice"),
            "color" => $this->getTimelineEntryColor("invoice"),
            "time" => $this->getTimelineEntryTime(),
            "type" => "Invoice",
            "name" => $this->getTimelineEntryName("invoice"),
            "desc" => $this->getTimelineEntryDesc("invoice"),
            "buttons" => $this->getTimelineEntryBtns("invoice"),
        ];
    }

    public static function calcTotalCollectedHoursAverage($monthYear, $type="month", $firm_id=0, $user="all", $mt="all") {
        $revenue = self::calcRevenue($monthYear, $type, $firm_id, $user, $mt);
        $billed_hours = PPTimeEntry::getTotalBilledHours($monthYear, $type, $firm_id, $user, $mt);
        $collection = self::calcCollectionSimple($monthYear, $type, $firm_id, $user, $mt);
        if ($user != "all") {
            $user_billed_hours = PPInvoiceLineItem::getTotalBilledHours($monthYear, $type, $firm_id, $user);
            $per = $billed_hours == 0 ? 0 : ($user_billed_hours / $billed_hours) * 100;
            if ($per != 0) {
                $final_collection = ($collection * $per) / 100;
            } else {
                $final_collection = 0;
            }
        } else {
            $final_collection = $collection;
        }

        if ($billed_hours > 0) {
            $rate = $revenue / $billed_hours;
        } else {
            $rate = 0;
        }
        if ($rate > 0) {
            $total_hours_collected = $final_collection / $rate;
        } else {
            $total_hours_collected = 0;
        }
       // dd("Revenue ".$revenue,"Billed Hours ".$billed_hours,"Collection ".$collection,"Final Collection ".$final_collection,"total hours collected".$total_hours_collected,"rate ".$rate,"User Billed Hours ".$user_billed_hours,"billed hours ".$billed_hours,"Percentage ".$per );
        return round($total_hours_collected, 2);
    }

    public static function calcTotalCollectedHoursAverageYearWise($financial_year, $firm_id=0, $user="all", $mt="all") {
        $revenue = array_sum(self::calcRevenueYearWise($financial_year, $firm_id, $user, $mt));
        $billed_hours = array_sum(PPTimeEntry::getTotalBilledHoursYearWise($financial_year, $firm_id, $user, $mt));
        $collection = array_sum(self::calcCollectionSimpleYearWise($financial_year, $firm_id, $user, $mt));
        if ($billed_hours > 0) {
            $rate = $revenue / $billed_hours;
        } else {
            $rate = 0;
        }
        if ($rate > 0) {
            $total_hours_collected = $collection / $rate;
        } else {
            $total_hours_collected = 0;
        }
        return round($total_hours_collected / 12, 2);
    }

}
