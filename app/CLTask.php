<?php

namespace App;

use App\Traits\TimelineTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CLTask extends Model
{
    use TimelineTrait;

    public $timestamps = false;
    protected $table = "cl_tasks";

    public function users()
    {
        return $this->HasMany('App\CLTaskAssignee', 'clio_task_id', 'id');
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
            $users[] = $value->name;
        }
        return implode(" - ", $users);
    }

    public function getStatus() {
        if ($this->status == "complete") {
            return "Green";
        } elseif ($this->status == "pending" || $this->status == "in_progress" || $this->status == "in_review") {
            $due_date = Carbon::parse($this->due_at);
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
        return date("m-d-Y (h:i A)", strtotime($this->due_at));
    }

    public static function calcTasksPerUser($monthYear, $year = "month", $firm_id=0, $type="open", $user) {
//       $monthYear = "2018-04";
//       $type = "open";
        $data = self::where("firm_id", $firm_id);
        if($user != "all") {
            $ref_id = CLUser::where("firm_id", $firm_id)->where("id",$user)->select("ref_id")->first();
            $ref_id = $ref_id->ref_id;
            $data = $data->whereHas('users', function($q)use($ref_id){
                $q->where('cl_task_assignees.ref_id', $ref_id);
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
            $data->where("status", "<>", "complete");
        } elseif ($type == "overdue") {
            $data->where("status", "<>", "complete")->where("due_at", "<", date("Y-m-d H:i:s"));
        } elseif ($type == "completed") {
            $data->where("status", "complete");
        }
         if ($data != null) {
             return $data->count();
         } else {
             return 0;
         }
    }
}
