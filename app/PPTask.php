<?php

namespace App;

use App\Traits\TimelineTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PPTask extends Model
{
    use TimelineTrait;

    public $timestamps = false;
    protected $table = "pp_tasks";

    public function users()
    {
        return $this->belongsToMany('App\PPUser', 'pp_task_user', 'pp_task_id', 'pp_user_id');
    }
    public static function getIdfromRefID($ref_id, $firm_id) {
        $row = self::select("id")->where("ref_id", $ref_id)->where("firm_id", $firm_id)->first();
        if ($row != null) {
            return $row->id;
        } else {
            return 0;
        }
    }
    public function getTimelineEntry () {
        return [
            "id" => $this->id,
            "icon" => $this->getTimelineEntryIcon("task"),
            "color" => $this->getTimelineEntryColor("task"),
            "time" => $this->getTimelineEntryTime(),
            "type" => "Activity",
            "name" => $this->getTimelineEntryName("task"),
            "desc" => $this->getTimelineEntryDesc("task"),
            "buttons" => $this->getTimelineEntryBtns("task"),
        ];
    }

    public function getUsers()
    {
        $users = [];
        foreach ($this->users as $key => $value) {
            $users[] = $value->display_name;
        }
        return implode(" - ", $users);
    }

    public function getStatus() {
        if ($this->status == "Completed") {
            return "Green";
        } elseif ($this->status == "InProgress" || $this->status == "NotCompleted") {
            $due_date = Carbon::parse($this->due_date);
            $date = Carbon::now();
            if ($due_date->diffInDays($date, false) <= 0) {
                return "Green";
            } elseif ($due_date->diffInDays($date, false) > 0 && $due_date->diffInDays($date, false) <= 3) {
                return "Yellow";
            } elseif ($due_date->diffInDays($date, false) > 3) {
                return "Red";
            }
        }
        return "Green";
    }

    public function getDueDate() {
        return date("m-d-Y (h:i A)", strtotime($this->due_date));
    }

    public static function calcTasksPerUser($monthYear, $year = "month", $firm_id=0, $type="open", $user) {
       $data = self::where('firm_id',$firm_id);
        if($user != "all") {
           $data = $data->whereHas('users', function($q)use($user){
               $q->where('pp_users.id', $user);
           });
       }

        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
            }
        }
        if ($type == "open") {
             $data->where("status", "NotCompleted");
        } elseif ($type == "overdue") {
             $data->where("status", "NotCompleted")->where("due_date", "<", date("Y-m-d H:i:s"));

        }elseif ($type == "completed") {
            $data->where("status", "Completed");
        }
        if ($data != null) {
            return $data->count();
        } else {
            return 0;
        }
    }
}
