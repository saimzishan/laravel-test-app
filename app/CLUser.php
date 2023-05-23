<?php

namespace App;

use App\Http\Libraries\HelperLibrary;
use Illuminate\Database\Eloquent\Model;

class CLUser extends Model
{
    public $timestamps = false;
    protected $table = "cl_users";

    public function tasks()
    {
        return $this->belongsToMany('App\CLTask', 'cl_task_assignees', 'ref_id', 'clio_task_id')
            ->withPivot(["firm_id", "type", "indentifier", "name", "enabled"]);
    }
    public function matters()
    {
        return $this->belongsToMany('App\CLMatter', 'cl_matter_users', 'clio_user_id', 'clio_matter_id');
    }


    public static function getIdfromRefID($ref_id, $firm_id) {
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
        return $this->name;
    }

    public function calculateFTEEquivilance() {
        return round(($this->hours_per_week * 4) / $this->fte_hours_per_month, 2);
    }

//    public static function calcTasksPerUser($monthYear, $year = "month", $firm_id=0, $type="open", $user) {
//        $data = self::where("firm_id", $firm_id)->where("id", $user)->withCount("tasks");
//        if ($monthYear!="") {
//            if ($year=="year") {
//                $data = $data->whereHas("tasks", function($q) use ($monthYear) {
//                    $q->whereRaw("cl_tasks.created_at >= '{$monthYear->from}' and cl_tasks.created_at <= '{$monthYear->to}'");
//                });
//            } elseif ($year=="month") {
//                $data = $data->whereHas("tasks", function($q) use ($monthYear) {
//                    $q->whereRaw("LEFT(cl_tasks.created_at, 7) = '{$monthYear}'");
//                });
//            } elseif ($year=="today") {
//                $data = $data->whereHas("tasks", function($q) use ($monthYear) {
//                    $q->whereRaw("LEFT(cl_tasks.created_at, 10) = '{$monthYear}'");
//                });
//            }
//        }
//        if ($type == "open") {
//            $data = $data->whereHas("tasks", function($q) {
//                $q->where("status", "<>", "complete");
//            });
//        } elseif ($type == "overdue") {
//            $data = $data->whereHas("tasks", function($q) {
//                $q->where("status", "<>", "complete");
//                $q->where("due_at", "<", date("Y-m-d H:i:s"));
//            });
//        } elseif ($type == "completed") {
//            $data = $data->whereHas("tasks", function($q) {
//                $q->where("status", "complete");
//            });
//        }
//        $val = $data->first();
//        if (isset($val->tasks_count)) {
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
        foreach ($data->get() as $usr) {
            $sum += $usr->rate_per_hour * $usr->monthly_billable_target;
        }
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

    public static function calcAvailableHour($monthYear, $year = "month", $firm_id=0, $user="all", $package="foundation") {
        $sum = 0;
        

        if ($package=="foundation") {
            $data = self::where("firm_id", $firm_id)
            ->where('can_be_calculated', true)
            ->where('enabled', true)
            ->where('subscription_type', "Attorney")
            ->select(['created_at', 'hours_per_week', 'id']);
        } else {
            $data = self::where("firm_id", $firm_id)
            ->where('can_be_calculated', true)
            ->select(['created_at', 'hours_per_week', 'id']);

        }

        if ($year=="year") {
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

    public static function calcAvailableHourYearWise($monthYear, $firm_id = 0, $user="all", $package="foundation") {
        $data = 0;
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data =+ self::calcAvailableHour($i->format("Y-m"), "month", $firm_id, $user, $package);
        }
        return round($data, 2);
    }

    public static function calcAvailableTimeTarget($monthYear, $year = "month", $firm_id=0, $user="all") {
        $sum = 0;
        $data = self::where("firm_id", $firm_id)
            ->where('can_be_calculated', true)
            ->select(['date_of_joining', 'id', 'monthly_billable_target']);
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
