<?php

namespace App;

use App\Traits\TimelineTrait;
use Illuminate\Database\Eloquent\Model;

class CLInvoice extends Model
{
    use TimelineTrait;

    public $timestamps = false;
    protected $table = "cl_invoices";

    public function matter() {
        return $this->belongsToMany("App\CLMatter", "cl_invoice_matters","clio_invoice_id", "clio_matter_id");
    }
    public function user () {
        return $this->belongsTo("App\CLUser", "clio_user_id");
    }
    public function contact () {
        return $this->belongsTo("App\CLContact", "clio_contact_id");
    }
    public function invoicelineitem () {
        return $this->hasMany("App\CLInvoiceLineItem", "clio_invoice_id","ref_id");
    }

    public static function getIdfromRefID($ref_id, $firm_id) {
        $row = self::select("id")->where("ref_id", $ref_id)->where("firm_id", $firm_id)->first();
        if ($row != null) {
            return $row->id;
        } else {
            return 0;
        }
    }

    public function getIssueDate() {
        return date("m-d-Y", strtotime($this->issued_at));
    }

    public function getContactDisplayName() {
        if ($this->contact != null) {
            return $this->contact->getDisplayName();
        } else {
            return "-";
        }
    }

    public function getMatterDisplayName() {
       if ($this->matter->count() > 0) {
            return $this->matter->first()->getName();
        } else {
            return "-";
        }
    }

    public function getStatus() {
        $definitions = Definition::getInvoicesDefinitions();
        if ($this->pending == 0) {
            return "Paid";
        } else {
            $cd = new \DateTime();
            $id = new \DateTime(substr($this->issued_at, 0, 10));
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
        $data = self::where('firm_id', $firm_id);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("issued_at >= '{$monthYear->from}' and issued_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(issued_at, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(issued_at, 10) = '{$monthYear}'");
            }
        }
        $data = $data->whereNotIn("state", ["deleted", "void","draft"]);
        if ($user != "all") {
            $data = $data->whereHas("matter.timeEntries", function($q)use($user){
                $q->where("clio_user_id", $user);
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
            $countTP = $countTP + $val->paid;
        }
        $final = $countT == 0 ? 0 : ($countTP / $countT) * 100;
        return round($final, 2);
    }

    public static function calcCollectionSimple($monthYear = "", $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null) {
        return self::calcCollectionRaw($monthYear, $year, $firm_id, $user, $mt, $mid)->sum("paid");
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
            $data[] = self::calcCollectionRaw($i->format("Y-m"), "month", $firm_id, $user, $mt)->sum("paid");
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

    public static function calcRevenue($monthYear, $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null, $cid=null) {
        $data = self::where("firm_id", $firm_id);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("issued_at >= '{$monthYear->from}' and issued_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(issued_at, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(issued_at, 10) = '{$monthYear}'");
            }
        }
        $data = $data->whereNotIn("state", ["deleted", "void","draft"]);
        if ($user != "all") {
            $data = $data->whereHas("matter.timeEntries", function($q)use($user){
                $q->where("clio_user_id", $user);
            });
        }
        if ($mt != "all") {
            $data = $data->whereHas("matter", function($q)use($mt){
                $q->where("matter_type", $mt);
            });
        }
        if ($mid != null) {
            $data = $data->whereHas("matter", function($q)use($mid){
                $q->where("cl_matters.id", $mid);
            });
        }
        if ($cid != null) {
            $data = $data->whereHas("contact", function($q)use($cid){
                $q->where("cl_contacts.id", $cid);
            });
        }
        return round($data->sum("total"), 2);
    }

    public static function calcRevenueYearWise($monthYear, $firm_id=0, $user="all", $mt="all") {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data[] = self::calcRevenue($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return $data;
    }

    public static function calcGrossProfit($monthYear, $year = "month", $firm_id=0, $user="all", $mt="all") {

        $revenue = self::calcRevenue($monthYear, "month", $firm_id, $user, $mt);
        $expense =  CLTimeEntry::calcExpense($monthYear, "month", $firm_id, $user, $mt);
        $result = $revenue - $expense;
        return round($result, 2);
    }

    public static function calcGrossProfitYearWise($monthYear, $firm_id=0, $user="all", $mt="all") {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $revenue = self::calcRevenue($i->format("Y-m"), "month", $firm_id, $user, $mt);
            $expense = CLTimeEntry::calcExpense($i->format("Y-m"), "month", $firm_id, $user, $mt);
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
        $billed_hours = CLInvoiceLineItem::getTotalBilledHours($monthYear, $type, $firm_id, $user, $mt);
        $collection = self::calcCollectionSimple($monthYear, $type, $firm_id, $user, $mt);
        if ($user != "all") {
            $user_billed_hours = CLInvoiceLineItem::getTotalBilledHours($monthYear, $type, $firm_id, $user);
            $per = $billed_hours == 0 ? 0 :($user_billed_hours / $billed_hours) * 100;
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
        return round($total_hours_collected, 2);
    }

    public static function calcTotalCollectedHoursAverageYearWise($financial_year, $firm_id=0, $user="all", $mt="all") {
        $revenue = array_sum(self::calcRevenueYearWise($financial_year, $firm_id, $user, $mt));
        $billed_hours = array_sum(CLInvoiceLineItem::getTotalBilledHoursYearWise($financial_year, $firm_id, $user, $mt));
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

    public static function calcCredit($monthYear, $year = "month", $firm_id=0) {
        $data = self::where("firm_id", $firm_id)
            ->where('type','Credit');
        if ($monthYear != "") {
            if ($year=="year") {
                $data = $data->whereRaw("issued_at >= '{$monthYear->from}' and issued_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(issued_at, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(issued_at, 10) = '{$monthYear}'");
            }
        }
        $temp = round($data->sum("total"), 2);
        if ($temp < 0){
            $temp = $temp * -1;
            return $temp;
        } else {
            return $temp;
        }
    }

}
