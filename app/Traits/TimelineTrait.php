<?php

namespace App\Traits;


use App\Http\Libraries\HelperLibrary;

trait TimelineTrait {
    public function getTimelineEntryIcon($type = "task") {
        if ($type == "task") {
            return "fa-tasks";
        } elseif ($type == "invoice") {
            return "fa-dollar-sign";
        } elseif ($type == "timeentry") {
            return "fa-hourglass";
        }
    }
    public function getTimelineEntryColor($type = "task") {
        if ($type == "task") {
            return "bg-theme-info";
        } elseif ($type == "invoice") {
            return "bg-theme-danger";
        } elseif ($type == "timeentry") {
            return "bg-theme-success";
        }
    }
    public function getTimelineEntryType($type = "task") {
        if ($type == "task") {
            return "Activity";
        } elseif ($type == "invoice") {
            return "Invoice";
        } elseif ($type == "timeentry") {
            return "Time Entry";
        }
    }
    public function getTimelineEntryTime() {
        return date("h:i A", strtotime($this->created_at));
    }
    public function getTimelineEntryName($type = "task") {
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            if ($type == "task") {
                return $this->subject;
            } elseif ($type == "invoice") {
                return optional($this->account)->display_name;
            } elseif ($type == "timeentry") {
                return optional($this->user)->display_name;
            }
        } else {
            if ($type == "task") {
                return $this->name;
            } elseif ($type == "invoice") {
                return $this->number;
            } elseif ($type == "timeentry") {
                return $this->note;
            }
        }
    }
    public function getTimelineEntryDesc($type = "task") {
        if ($type == "task") {
            return "<strong>Users:</strong> {$this->getUsers()}";
        } elseif ($type == "invoice") {
            return "Total: <span>$</span>{$this->total} | Paid: <span>$</span>".($this->paid==""?0:$this->paid);
        } elseif ($type == "timeentry") {
            return $this->description;
        }
    }
    public function getTimelineEntryBtns($type = "task") {
        $rows = [];
        if ($type == "task") {
            $status = $this->getStatus();
            if ($status == "Red") {
                $col = "btn-theme-danger";
            } elseif ($status == "Yellow") {
                $col = "btn-theme-warning";
            } elseif ($status == "Green") {
                $col = "btn-theme-success";
            } else {
                $col = "btn-theme-primary";
            }
            $rows[] = '<a class="btn '.$col.' btn-sm text-white">Status: '.$status.'</a> ';
            $rows[] = '<a class="btn btn-theme-info btn-sm text-white">Due Date: '.$this->getDueDate().'</a> ';
        } elseif ($type == "invoice") {
            $status = $this->getStatus();
            if ($status == "Current") {
                $col = "btn-theme-success";
            } elseif ($status == "Late") {
                $col = "btn-theme-info";
            } elseif ($status == "Delinquent") {
                $col = "btn-theme-info";
            } elseif ($status == "Collection") {
                $col = "btn-theme-danger";
            } else {
                $col = "btn-primary";
            }
            $rows[] = '<a class="btn '.$col.' btn-sm text-white">Status: '.$status.'</a> ';
        } elseif ($type == "timeentry") {
            $rows[] = '<a class="btn btn-info btn-sm text-white">Hours: '.$this->getHours().'</a> ';
        }
        return $rows;
    }
}