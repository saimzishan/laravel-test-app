<?php

namespace App;

use DateTime;
use Illuminate\Database\Eloquent\Model;

class PPMatter extends Model
{
    public $timestamps = false;
    protected $table = "pp_matters";

    public function account () {
        return $this->belongsTo("App\PPAccount", "pp_account_id");
    }
    public function invoices () {
        return $this->hasMany("App\PPInvoice", "pp_matter_id");
    }
    public function timeEntries () {
        return $this->hasMany("App\PPTimeEntry", "pp_matter_id");
    }
    public function tasks () {
        return $this->hasMany("App\PPTask", "pp_matter_id");
    }
    public function users () {
        return $this->belongsToMany("App\PPUser", "pp_matter_user", "pp_matter_id", "pp_user_id");
    }
    public function expenses () {
        return $this->hasMany("App\PPExpense", "pp_matter_id");
    }

    public static function getIDfromRefID($ref_id, $firm_id) {
        $row = self::select("id")->where("ref_id", $ref_id)->where("firm_id", $firm_id)->first();
        if ($row != null) {
            return $row->id;
        } else {
            return 0;
        }
    }

    public function getName() {
        return $this->name;
    }

    public function getCreatedDate()
    {
        return date("m-d-Y", strtotime($this->created_at));
    }

    public function getCreatedDateRaw()
    {
        return date("Ymd", strtotime($this->created_at));
    }

    public function getDaysFileOpen()
    {
        $now = time(); // or your date as well
        $your_date = strtotime($this->created_at);
        $datediff = $now - $your_date;
        return round($datediff / (60 * 60 * 24));
    }
    public function getActivitiesCount() {
        return $this->tasks->count();
    }
    public function getInvoicesCount() {
        return $this->invoices->count();
    }
    public function getTimeEntriesCount() {
        return $this->timeEntries->count();
    }
    public function getContactsCount() {
        if ($this->pp_account_id == null) {
            return 0;
        }
        return $this->account->contacts->count();
    }
    public function getTimeline() {
        $data = $this->tasks;
        $data = $data->merge($this->invoices);
        $data = $data->merge($this->timeEntries);
        $data = $data->sortByDesc('created_at');
        $data = $data->groupBy(function ($item, $key) {
            return date("m-d-Y", strtotime(substr($item["created_at"], 0, 10)));
        });
        $data = $data->transform(function ($value, $key) {
            return $value->transform(function ($item, $key) {
                return $item->getTimelineEntry();
            });
        });
        return $data;
    }

    public static function calcNewMattersPerAttorney($monthYear, $year = "month", $firm_id=0) {
        $data = self::where("firm_id", $firm_id);
        $attorneys = PPUser::where("firm_id", $firm_id)->where("type", "Owner (Attorney)");
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
                $attorneys = $attorneys->whereRaw("date_of_joining <= '".substr($monthYear->to, 0, 10)."'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
                $attorneys = $attorneys->whereRaw("date_of_joining <= '".date("Y-m-t", strtotime($monthYear))."'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
                $attorneys = $attorneys->whereRaw("date_of_joining <= '".date("Y-m-d", strtotime($monthYear))."'");
            }
        }
        return $attorneys->count() == 0 ? 0 : $data->count() / $attorneys->count();
    }

    public static function calcNewMattersPerAttorneyYearWise($monthYear, $firm_id=0) {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data[] = self::calcNewMattersPerAttorney($i->format("Y-m"), "month", $firm_id);
        }
        return $data;
    }

    public static function calcNewClientsPerAOP($monthYear, $year = "month", $firm_id=0) {
        $return = [];
        $data = self::where("firm_id", $firm_id);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'")
                    ->whereHas("account", function($q) use ($monthYear){
                        $q->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
                    });
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'")
                    ->whereHas("account", function($q) use ($monthYear) {
                        $q->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
                    });
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'")
                    ->whereHas("account", function($q) use ($monthYear) {
                        $q->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
                    });
            }
        }
        $data = $data->get()->mapToGroups(function ($item, $key) {
            return [$item['matter_type'] => $item];
        });
        foreach ($data as $key => $value) {
            foreach ($value as $v) {
                if ($key != "") {
                    $return[$key] = $v->account()->count();
                }
            }
        }
        return $return;
    }

    public static function calcNewClientsPerAOPYearWise($monthYear, $firm_id=0) {
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        $arr = [];
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $tmp = self::calcNewClientsPerAOP($i->format("Y-m"), "month", $firm_id);
            foreach ($tmp as $key => $value) {
                if (isset($arr[$key])) {
                    $arr[$key] += $value;
                } else {
                    $arr[$key] = $value;
                }
                $arr[$key] = round($arr[$key], 2);
            }
        }
        return $arr;
    }
    public static function calcNewMattersPerAOP($monthYear, $year = "month", $firm_id=0) {
        $data = self::where("firm_id", $firm_id);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
            }
        }
        $data = $data->get()->mapToGroups(function ($item, $key) {
            if ($item['matter_type'] == "") {
                return ['Others' => $item];
            } else {
                return [$item['matter_type'] => $item];
            }
        })->transform(function ($item, $key) {
            return $item->count();
        });
        return $data;
    }

    public static function calcNewMattersPerAOPYearWise($monthYear, $firm_id=0) {
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        $arr = [];
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $tmp = self::calcNewMattersPerAOP($i->format("Y-m"), "month", $firm_id)->toArray();
            foreach ($tmp as $key => $value) {
                if (isset($arr[$key])) {
                    $arr[$key] += $value;
                } else {
                    $arr[$key] = $value;
                }
                $arr[$key] = round($arr[$key], 2);
            }
        }
        return $arr;
    }

    public static function calcTop5AOPPerRevenue($monthYear, $year = "month", $firm_id=0) {
        $ret = [];
        $data = self::where("firm_id", $firm_id);
        $aops = self::where("firm_id", $firm_id);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
                $aops = $aops->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
                $aops = $aops->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
                $aops = $aops->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
            }
        }
        $aops = $aops->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        foreach ($aops as $mt) {
            $matters = clone $data;
            $matters = $matters->where("matter_type", $mt->matter_type);
            if (!isset($ret[$mt->matter_type])) {
                $ret[$mt->matter_type] = 0;
            }
            foreach ($matters->get() as $matter) {
                $ret[$mt->matter_type] += round($matter->invoices()->sum("total"), 2);
            }
        }
        arsort($ret);
        return array_slice($ret, 0, 5, true);
    }

    public static function calcTop5AOPPerRevenueYearWise($monthYear, $firm_id=0) {
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        $arr = [];
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $tmp = self::calcTop5AOPPerRevenue($i->format("Y-m"), "month", $firm_id);
            foreach ($tmp as $key => $value) {
                if (isset($arr[$key])) {
                    $arr[$key] += $value;
                } else {
                    $arr[$key] = $value;
                }
                $arr[$key] = round($arr[$key], 2);
            }
        }
        arsort($arr);
        return array_slice($arr, 0, 5, true);
    }
    public static function calcTop5AOPPerOutstandingDues($monthYear, $year = "month", $firm_id=0) {
        $ret = [];
        $data = self::where("firm_id", $firm_id);
        $aops = self::where("firm_id", $firm_id);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
                $aops = $aops->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
                $aops = $aops->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
                $aops = $aops->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
            }
        }
        $aops = $aops->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        foreach ($aops as $mt) {
            $matters = clone $data;
            $matters = $matters->where("matter_type", $mt->matter_type);
            if (!isset($ret[$mt->matter_type])) {
                $ret[$mt->matter_type] = 0;
            }
            foreach ($matters->get() as $matter) {
                $ret[$mt->matter_type] += round($matter->invoices()->where('invoice_type', 'Sale')->sum("total_outstanding"), 2);
            }
        }
        arsort($ret);
        return array_slice($ret, 0, 5, true);
    }

    public static function calcTop5AOPPerOutstandingDuesYearWise($monthYear, $firm_id=0) {
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        $arr = [];
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $tmp = self::calcTop5AOPPerOutstandingDues($i->format("Y-m"), "month", $firm_id);
            foreach ($tmp as $key => $value) {
                if (isset($arr[$key])) {
                    $arr[$key] += $value;
                } else {
                    $arr[$key] = $value;
                }
                $arr[$key] = round($arr[$key], 2);
            }
        }
        arsort($arr);
        return array_slice($arr, 0, 5, true);
    }
    public static function calcTop5AOPPerGPM($monthYear, $year = "month", $firm_id=0) {
        $ret = [];
        $data = self::where("firm_id", $firm_id);
        $aops = self::where("firm_id", $firm_id);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
                $aops = $aops->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
                $aops = $aops->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
                $aops = $aops->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
            }
        }
        $aops = $aops->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        foreach ($aops as $mt) {
            $matters = clone $data;
            $matters = $matters->where("matter_type", $mt->matter_type);
            if (!isset($ret[$mt->matter_type])) {
                $ret[$mt->matter_type] = 0;
            }
            foreach ($matters->get() as $matter) {
                $ret[$mt->matter_type] += round($matter->invoices()->where('invoice_type', 'Sale')->sum("total") - $matter->expenses()->sum("amount"), 2);
            }
        }
        arsort($ret);
        return array_slice($ret, 0, 5, true);
    }

    public static function calcTop5AOPPerGPMYearWise($monthYear, $firm_id=0) {
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        $arr = [];
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $tmp = self::calcTop5AOPPerGPM($i->format("Y-m"), "month", $firm_id);
            foreach ($tmp as $key => $value) {
                if (isset($arr[$key])) {
                    $arr[$key] += round($value, 2);
                } else {
                    $arr[$key] = round($value, 2);
                }
                $arr[$key] = round($arr[$key], 2);
            }
        }
        arsort($arr);
        return array_slice($arr, 0, 5, true);
    }
    public static function calcAOPPerRevenue($monthYear, $year = "month", $firm_id=0) {
        $ret = [];
        $data = self::where("firm_id", $firm_id);
        $aops = self::where("firm_id", $firm_id);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
                $aops = $aops->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
                $aops = $aops->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
                $aops = $aops->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
            }
        }
        $aops = $aops->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        foreach ($aops as $mt) {
            $matters = clone $data;
            $matters = $matters->where("matter_type", $mt->matter_type);
            if (!isset($ret[$mt->matter_type])) {
                $ret[$mt->matter_type] = 0;
            }
            foreach ($matters->get() as $matter) {
                $ret[$mt->matter_type] += round($matter->invoices()->where("invoice_type", "Sale")->sum("total"), 2);
            }
        }
        return $ret;
    }

    public static function calcAOPPerRevenueYearWise($monthYear, $firm_id=0) {
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        $arr = [];
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $tmp = self::calcAOPPerRevenue($i->format("Y-m"), "month", $firm_id);
            foreach ($tmp as $key => $value) {
                if (isset($arr[$key])) {
                    $arr[$key] += $value;
                } else {
                    $arr[$key] = $value;
                }
                $arr[$key] = round($arr[$key], 2);
            }
        }
//        dd($arr);
        return $arr;
    }
    public static function calcAOPPerGPM($monthYear, $year = "month", $firm_id=0) {
        $ret = [];
        $data = self::where("firm_id", $firm_id);
        $aops = self::where("firm_id", $firm_id);
        $users = PPUser::where("firm_id", $firm_id)
            ->where("can_be_calculated", true)
            ->select(["hours_per_week", "cost_per_hour","display_name","id"]);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
                $aops = $aops->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
                $users = $users->whereRaw("date_of_joining <= '{$monthYear->from}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
                $aops = $aops->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
                $users = $users->whereRaw("date_of_joining <= '{$monthYear}-01'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
                $aops = $aops->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
                $users = $users->whereRaw("date_of_joining <= '{$monthYear}-01'");
            }
        }
        $aops = $aops->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        $expense = 0;

        foreach ($users->get() as $v) {
            $u = PPTimeEntry::getTotalBillableHours($monthYear,$year,$firm_id,$v->id);
            $expense += $u * $v->cost_per_hour;
        }
        foreach ($aops as $mt) {
            $matters = clone $data;
            $matters = $matters->where("matter_type", $mt->matter_type);
            if (!isset($ret[$mt->matter_type])) {
                $ret[$mt->matter_type] = 0;
            }
            foreach ($matters->get() as $matter) {
                $e = $matter->expenses()->sum("amount");
                $ret[$mt->matter_type] += round($matter->invoices()->where('invoice_type', 'Sale')->sum("total") - $e, 2);
            }
            $ret[$mt->matter_type] = $ret[$mt->matter_type] - $expense;
        }
        return $ret;
    }

    public static function calcAOPPerGPMYearWise($monthYear, $firm_id=0) {
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        $arr = [];
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $tmp = self::calcAOPPerGPM($i->format("Y-m"), "month", $firm_id);
            foreach ($tmp as $key => $value) {
                if (isset($arr[$key])) {
                    $arr[$key] += round($value, 2);
                } else {
                    $arr[$key] = round($value, 2);
                }
                $arr[$key] = round($arr[$key], 2);
            }
        }
        return $arr;
    }

    public static function calcOpenMatters($monthYear, $year = "month", $firm_id=0) {
        $data = self::where("firm_id", $firm_id)->where("status", "Open");
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
            }
        }
        return $data->count();
    }

}
