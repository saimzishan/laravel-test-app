<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FirmPackage extends Model
{
    public function getStartDate() {
        return date("m-d-Y", strtotime($this->start_date));
    }
    public function getEndDate() {
        return date("m-d-Y", strtotime($this->end_date));
    }
    public function getStatus() {
        return $this->is_active ? "Active" : "In-Active";
    }
    public function getPackageName() {
        switch ($this->package_key) {
            case "foundation":
                return "Foundation";
            break;
            case "foundation_plus":
                return "Foundation +";
            break;
            case "enhanced":
                return "Enhanced";
            break;
            default:
                return "-";
        }
    }
}
