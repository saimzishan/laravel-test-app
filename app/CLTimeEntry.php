<?php

namespace App;

use App\Http\Libraries\HelperLibrary;
use App\Traits\TimelineTrait;
use Illuminate\Database\Eloquent\Model;

class CLTimeEntry extends Model
{
    use TimelineTrait;

    public $timestamps = false;
    protected $table = "cl_time_entries";

    public function user() {
        return $this->belongsTo('App\CLUser', "clio_user_id");
    }
    public function matter() {
        return $this->belongsTo("App\CLMatter", "clio_matter_id");
    }
    public static function getIdfromRefID($ref_id, $firm_id) {
        $row = self::select("id")->where("ref_id", $ref_id)->where("firm_id", $firm_id)->first();
        if ($row != null) {
            return $row->id;
        } else {
            return 0;
        }
    }

    public static function getWorkingDays($startDate,$endDate,$holidays=null){
        // do strtotime calculations just once
        $endDate = strtotime($endDate);
        $startDate = strtotime($startDate);


        //The total number of days between the two dates. We compute the no. of seconds and divide it to 60*60*24
        //We add one to inlude both dates in the interval.
        $days = ($endDate - $startDate) / 86400 + 1;

        $no_full_weeks = floor($days / 7);
        $no_remaining_days = fmod($days, 7);

        //It will return 1 if it's Monday,.. ,7 for Sunday
        $the_first_day_of_week = date("N", $startDate);
        $the_last_day_of_week = date("N", $endDate);

        //---->The two can be equal in leap years when february has 29 days, the equal sign is added here
        //In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
        if ($the_first_day_of_week <= $the_last_day_of_week) {
            if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) $no_remaining_days--;
            if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) $no_remaining_days--;
        }
        else {
            // (edit by Tokes to fix an edge case where the start day was a Sunday
            // and the end day was NOT a Saturday)

            // the day of the week for start is later than the day of the week for end
            if ($the_first_day_of_week == 7) {
                // if the start date is a Sunday, then we definitely subtract 1 day
                $no_remaining_days--;

                if ($the_last_day_of_week == 6) {
                    // if the end date is a Saturday, then we subtract another day
                    $no_remaining_days--;
                }
            }
            else {
                // the start date was a Saturday (or earlier), and the end date was (Mon..Fri)
                // so we skip an entire weekend and subtract 2 days
                $no_remaining_days -= 2;
            }
        }

        //The no. of business days is: (number of weeks between the two dates) * (5 working days) + the remainder
        //---->february in none leap years gave a remainder of 0 but still calculated weekends between first and last day, this is one way to fix it
        $workingDays = $no_full_weeks * 5;
        if ($no_remaining_days > 0 )
        {
            $workingDays += $no_remaining_days;
        }

        // //We subtract the holidays
        // foreach($holidays as $holiday){
        //     $time_stamp=strtotime($holiday);
        //     //If the holiday doesn't fall in weekend
        //     if ($startDate <= $time_stamp && $time_stamp <= $endDate && date("N",$time_stamp) != 6 && date("N",$time_stamp) != 7)
        //         $workingDays--;
        // }

        return $workingDays;
    }

    public static function getTotalBilledHours($month="", $year = false, $firm_id = 0, $user="all", $mt="all") {
        $data = self::whereHas('user',function($q){
                $q->where("hours_per_week","<>","0");
                $q->where('can_be_calculated', true);
            })
            ->where("total", "<>", 0)
            ->where('type', "TimeEntry")
            ->where("flat_rate","<>","1")
            ->where('firm_id',$firm_id);
        if ($month!= "") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$month->from}' and created_at <= '{$month->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$month}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$month}'");
            }
        }
        if ($user != "all") {
            $data->where("clio_user_id", $user);
        }
        if ($mt != "all") {
            $data->whereHas("matter", function($q)use($mt){
                $q->where("matter_type", $mt);
            });
        }
        $count = 0;
        foreach($data->get() as $val)
        {
            $count = $count + $val->quantity_in_hours;
        }
        return $count;
    }

    public static function getTotalBilledHoursYearWise($monthYear, $firm_id = 0, $user="all", $mt="all") {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data[] = self::getTotalBilledHours($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return $data;
    }

    public static function getTotalBillableHours($month="", $year = false, $firm_id = 0, $user="all", $mt="all") {
        if ($month!= "") {
            if ($year=="year") {
                $data = self::whereRaw("created_at >= '{$month->from}' and created_at <= '{$month->to}'");
            } elseif ($year=="month") {
                $data = self::whereRaw("LEFT(created_at, 7) = '{$month}'");
            } elseif ($year=="today") {
                $data = self::whereRaw("LEFT(created_at, 10) = '{$month}'");
            }
            $data = $data->where('firm_id',$firm_id)->select(['firm_id', 'clio_matter_id', 'type', 'clio_user_id', 'quantity_in_hours']);
        } else {
            $data = self::where('firm_id',$firm_id)->select(['firm_id', 'clio_matter_id', 'type', 'clio_user_id', 'quantity_in_hours']);
        }
        $data = $data->where('type', "TimeEntry")->where("flat_rate","<>","1");
        if ($user != "all") {
            $data->where("clio_user_id", $user);
        }
        if ($mt != "all") {
            $data->whereHas("matter", function($q)use($mt){
                $q->where("matter_type", $mt);
            });
        }
        $count = 0.00;
        foreach($data->get() as $val)
        {
            $count = $count + $val->quantity_in_hours;
        }
        return $count;
    }
    public static function getTotalBillableHoursYearWise($monthYear, $firm_id = 0, $user="all", $mt="all") {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data[] = self::getTotalBillableHours($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return $data;
    }

    public static function getTotalBillableHoursUtilization($month="", $year = "month", $firm_id = 0, $user="all", $mt="all") {
        $data = self::whereHas('user',function($q){
                $q->where("hours_per_week","<>","0");
            })
            ->where('firm_id', $firm_id)
            ->where('type', "TimeEntry")
            ->where("flat_rate","<>","1")
            ->select(['clio_user_id', 'clio_matter_id', 'created_at', 'quantity_in_hours']);
        if ($month!= "") {
            if ($year=="year") {
                $data = $data->where("created_at" , ">=", "'{$month->from}'" )->where("created_at",  "<=", "'{$month->to}'");
            } elseif ($year="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$month}'");
            } elseif ($year="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$month}'");
            }
            if ($user != "all") {
                $data->where("clio_user_id", $user);
            }
            if ($mt != "all") {
                $data->whereHas("matter", function($q)use($mt){
                    $q->where("matter_type", $mt);
                });
            }
        }
        $count = 0.00;
        foreach($data->get() as $val)
        {
            $count = $count + $val->quantity_in_hours;
        }
        return round($count, 2);
    }

    public static function calcUtilization($yearMonth = null, $year = "month", $firm_id = 0, $user="all", $mt="all")
    {
        $yearMonth = $yearMonth == null ? date("Y-m") : $yearMonth;
        $res = CLUser::where("hours_per_week", "<>", "0")->where('firm_id',$firm_id)
            ->where('can_be_calculated', true);
        if ($user != "all") {
            $res = $res->where("id", $user);
        }
        if ($year=="year") {
            $res->where('date_of_joining', "<=", substr($yearMonth->to, 0, 10));
        } elseif ($year=="month") {
            $res->where('date_of_joining', "<=", date("Y-m-t", strtotime("{$yearMonth}-01")));
        } elseif ($year=="today") {
            $res->where('date_of_joining', "<=", date("Y-m-t", strtotime($yearMonth)));
        }
        $res = $res->get();
        $TBH = self::getTotalBillableHoursUtilization($yearMonth, $year, $firm_id, $user, $mt);
        $totalhours = 0;
        foreach ($res as $r) {
            if ($year=="year") {
                $end = substr($yearMonth->to, 0, 10); // or your date as well
                $start = substr($yearMonth->from, 0, 10); // or your date as well
            } elseif ($year=="month") {
                $end = date("{$yearMonth}-t"); // or your date as well
                $start = date("{$yearMonth}-01"); // or your date as well
            } elseif ($year=="today") {
                $end = $yearMonth; // or your date as well
                $start = $yearMonth; // or your date as well
            }
            $your_date = date("Y-m-d", strtotime($r->date_of_application));
            $days = self::getWorkingDays($your_date, $end);
            $perday = ((int)$r->hours_per_week) / 5;
            if ($your_date < $end) {
                $daysThisYear = self::getWorkingDays($start, $end);
                $total = $perday * $daysThisYear;
            } else {
                $total = $perday * $days;
            }
            $totalhours += $total;
        }
        if ($TBH != 0 && $totalhours != 0) {
            return round((($TBH / $totalhours) * 100));
        } else {
            return 0;
        }
    }

    public static function calcUtilizationYearWise($monthYear, $firm_id = 0, $user="all", $mt="all") {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data[] = self::calcUtilization($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return $data;
    }

    public static function calcUtilizationYearWiseAverage($monthYear, $firm_id = 0, $user="all", $mt="all") {
        $data = 0;
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data += self::calcUtilization($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return round($data / 12, 2);
    }
    public static function calcUtilizationYearWiseAverageHours($monthYear, $firm_id = 0, $user="all", $mt="all") {
        $data = 0;
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data += self::getTotalBillableHoursUtilization($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return round($data / 12, 2);
    }

    public static function calcRealization($yearMonth = null, $year = "month", $firm_id = 0, $user="all", $mt="all")
    {
        $total = self::getTotalBillableHours($yearMonth, $year, $firm_id, $user, $mt);
        $TBH = self::getTotalBilledHours($yearMonth, $year, $firm_id, $user, $mt);

        if ($total != 0) {
            return round((($TBH/$total)*100));
        } else {
            return 0;
        }
    }

    public static function calcRealizationYearWise($monthYear, $firm_id = 0, $user="all", $mt="all") {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data[] = self::calcRealization($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return $data;
    }

    public static function calcRealizationYearWiseAverage($monthYear, $firm_id = 0, $user="all", $mt="all") {
        $data = 0;
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data += self::calcRealization($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return round($data / 12, 2);
    }
    public static function calcTotalBilledHoursYearWise($monthYear, $firm_id = 0, $user="all", $mt="all") {
        $data = 0;
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data += self::getTotalBilledHours($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return round($data / 12, 2);
    }

    public static function calcExpense($monthYear, $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null, $package=null) {
        $data = self::where("firm_id", $firm_id)->whereIn("type", ["ExpenseEntry", "HardCostEntry", "SoftCostEntry"]);
        $users = CLUser::where("firm_id", $firm_id)->where("can_be_calculated", true);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
                $users = $users->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
                $users = $users->whereRaw("created_at <= '".date("Y-m-t 23:59:59", strtotime($monthYear))."'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
                $users = $users->whereRaw("created_at <= '".date("Y-m-d 23:59:59", strtotime($monthYear))."'");
            }
        }
        if ($user != "all") {
            $data = $data->where("clio_user_id", $user);
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
        $final = $data->sum("total");
        if (HelperLibrary::getFirmPackageName($package) != "foundation") {
            foreach ($users->get() as $v) {
                $u = Self::getTotalBillableHours($monthYear,$year,$firm_id,$v->id,"all");
                $final += $u * $v->cost_per_hour;
            }
        }
        return round($final, 2);
    }

    public static function calcExpenseYearWise($monthYear, $firm_id=0, $user="all", $mt="all") {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data[] = self::calcExpense($i->format("Y-m"), "month", $firm_id, $user, $mt);
        }
        return $data;
    }

    public function getTimelineEntry () {
        return [
            "id" => $this->id,
            "icon" => $this->getTimelineEntryIcon("timeentry"),
            "color" => $this->getTimelineEntryColor("timeentry"),
            "time" => $this->getTimelineEntryTime(),
            "type" => "Time Entry",
            "name" => $this->getTimelineEntryName("timeentry"),
            "desc" => $this->getTimelineEntryDesc("timeentry"),
            "buttons" => $this->getTimelineEntryBtns("timeentry"),
        ];
    }

    public function getHours() {
        return $this->quantity_in_hours;
    }

}
