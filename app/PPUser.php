<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PPUser extends Model
{
    public $timestamps = false;
    protected $table = 'pp_users';

    public function tasks()
    {
        return $this->belongsToMany('App\PPTask', 'pp_task_user', 'pp_user_id', 'pp_task_id');
    }
    public function timeEntry() {
        return $this->hasMany('App\PPTimeEntry', 'billed_by_user_id', 'id');
    }
    public function invoiceLineItems()
    {
        return $this->hasMany('App\PPInvoiceLineItem', 'billed_by', 'display_name')->where("firm_id", $this->firm_id);
    }

    public static function getIDfromRefID($ref_id, $firm_id) {
        $row = self::select("id")->where("ref_id", $ref_id)->where("firm_id", $firm_id)->first();
        if ($row != null) {
            return $row->id;
        } else {
            return 0;
        }
    }
    public function getDateOfJoining() {
        return date("m-d-Y", strtotime($this->date_of_joining));
    }
    public function getName() {
        return $this->display_name;
    }
    public function calculateFTEEquivilance() {
        return round(($this->hours_per_week * 4) / $this->fte_hours_per_month, 2);
    }
// Old Fuction for Generating Front End Table
//    public static function calcTasksPerUser($monthYear, $year = "month", $firm_id=0, $type="open", $user) {
//        $data = self::where("firm_id", $firm_id)->where("id", $user);
//        if ($monthYear!="") {
//            if ($year=="year") {
//                $data = $data->whereHas("tasks", function($q) use ($monthYear) {
//                    $q->whereRaw("pp_tasks.created_at >= '{$monthYear->from}' and pp_tasks.created_at <= '{$monthYear->to}'");
//                });
//            } elseif ($year=="month") {
//                $data = $data->whereHas("tasks", function($q) use ($monthYear) {
//                    $q->whereRaw("LEFT(pp_tasks.created_at, 7) = '{$monthYear}'");
//                });
//            } elseif ($year=="today") {
//                $data = $data->whereHas("tasks", function($q) use ($monthYear) {
//                    $q->whereRaw("LEFT(pp_tasks.created_at, 10) = '{$monthYear}'");
//                });
//            }
//        }
//        if ($type == "open") {
//            $data = $data->whereHas("tasks", function($q) {
//                $q->where("status", "NotCompleted");
//            });
//        } elseif ($type == "overdue") {
//            $data = $data->whereHas("tasks", function($q) {
//                $q->where("status", "NotCompleted");
//                $q->where("due_date", "<", date("Y-m-d H:i:s"));
//            });
//        }elseif ($type == "completed") {
//            $data = $data->whereHas("tasks", function($q) {
//                $q->where("status", "Completed");
//            });
//        }
//        $val = $data->first();
//        if ($val->tasks->count()) {
//            return $val->tasks_count;
//        } else {
//            return 0;
//        }
//    }
//
//    public static function calcTasksPerUserYearWise($monthYear, $firm_id=0, $type="open", $user) {
//        $data = [];
//        $begin = new \DateTime(substr($monthYear->from, 0, 10));
//        $end = new \DateTime(substr($monthYear->to, 0, 10));
//        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
//            $data[] = self::calcTasksPerUser($i->format("Y-m"), "month", $firm_id, $type, $user);
//        }
//        return $data;
//    }

    public static function calcRevenueTarget($monthYear, $year = "month", $firm_id=0, $user="all") {
        $sum = 0;
        $data = self::where("firm_id", $firm_id)
            ->where('can_be_calculated', true);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->where("created_at" ,"<=", date("Y-m-t 23:59:59", strtotime("{$monthYear}-01")));
            } elseif ($year=="today") {
                $data = $data->where("created_at", "<=", "{$monthYear} 23:59:59");
            }
        }
        if ($user != "all") {
            $data = $data->where("id", $user);
        }
        $sum = $data->select(\DB::raw("sum(rate_per_hour * monthly_billable_target) as someVal"))->first()->someVal;
        return round($sum, 2);
    }

    public static function calcExpenseTarget($monthYear, $year = "month", $firm_id=0, $user="all") {
        $sum = 0;
        $data = self::where("firm_id", $firm_id)
            ->where('can_be_calculated', true);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->where("created_at" ,"<=", date("Y-m-t 23:59:59", strtotime("{$monthYear}-01")));
            } elseif ($year=="today") {
                $data = $data->where("created_at", "<=", "{$monthYear} 23:59:59");
            }
        }
        if ($user != "all") {
            $data = $data->where("id", $user);
        }
        foreach ($data->get() as $usr) {
            $sum += $usr->cost_per_hour * ($usr->hours_per_week * 4);
        }
        return round($sum, 2);
    }

    public static function calcAvailableHour($monthYear, $year = "month", $firm_id=0, $user="all") {
        $sum = 0;
        $data = self::where("firm_id", $firm_id)
            ->where('can_be_calculated', true)->select(["created_at","hours_per_week","id"]);
        if ($year=="year") {
//            $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            $data = $data->where("created_at",  ">=", "'{$monthYear->from}'")->where("created_at" ,"<=", "'{$monthYear->to}'");
        } elseif ($year=="month") {
            $data = $data->where("created_at" ,"<=", date("Y-m-t 23:59:59", strtotime("{$monthYear}-01")));
        } elseif ($year=="today") {
            $data = $data->where("created_at", "<=", "{$monthYear} 23:59:59");
        }

        if ($user != "all") {
            $data = $data->where("id", $user);
        }
        foreach ($data->get() as $usr) {
            $sum += $usr->hours_per_week * 4;
        }
        return round($sum, 2);
    }

    public static function calcAvailableHourYearWise($monthYear, $firm_id = 0, $user="all") {
        $data = 0;
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data =+ self::calcAvailableHour($i->format("Y-m"), "month", $firm_id, $user);
        }
        return round($data, 2);
    }

    public static function calcAvailableTimeTarget($monthYear, $year = "month", $firm_id=0, $user="all") {
        $sum = 0;
        $data = self::where("firm_id", $firm_id)
            ->where('can_be_calculated', true)->select(["id","date_of_joining","monthly_billable_target"]);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("date_of_joining >= '{$monthYear->from}' and date_of_joining <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->where("date_of_joining" ,"<=", date("Y-m-t", strtotime("{$monthYear}-01")));
            } elseif ($year=="today") {
                $data = $data->where("date_of_joining", "<=", "{$monthYear}");
            }
        }
        if ($user != "all") {
            $data = $data->where("id", $user);
        }
        foreach ($data->get() as $usr) {
            $sum += $usr->monthly_billable_target;
        }
        return round($sum, 2);
    }

}
