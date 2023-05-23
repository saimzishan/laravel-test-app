<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CLContact extends Model
{
    public $timestamps = false;
    protected $table = "cl_contacts";

    public function invoices() {
        return $this->hasMany("App\CLInvoice", "clio_contact_id");
    }

    public function matters() {
        return $this->belongsToMany("App\CLMatter", "cl_matter_contacts", "clio_contact_id", "clio_matter_id");
    }

    public static function getIdfromRefID($ref_id, $firm_id) {
        $row = self::select("id")->where("ref_id", $ref_id)->where("firm_id", $firm_id)->first();
        if ($row !=null) {
            return $row->id;
        } else {
            return 0;
        }
    }

    public function getDisplayName() {
        return $this->name;
    }

    public function getInvoicesTotalOutstanding() {
        return round(optional($this->invoices)->whereNotIn("state", ["deleted", "void"])->sum("due"), 2);
    }

    public function getInvoicesTotal() {
        return round(optional($this->invoices)->whereNotIn("state", ["deleted", "void"])->sum("total"), 2);
    }

    public static function calcPrimaryClients($monthYear, $year = "month", $firm_id=0) {
        $data = self::where("firm_id", $firm_id)->where("is_client", 1);
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

    public static function calcNewClientsPerType($monthYear, $year = "month", $firm_id=0, $type="") {
        $data = self::where("firm_id", $firm_id)->where("is_client", 1);
        $attorneys = CLUser::where("firm_id", $firm_id)->where("type", $type);
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

    public static function calcNewClientsPerAttorney($monthYear, $year = "month", $firm_id=0) {
        return self::calcNewClientsPerType($monthYear, $year, $firm_id, "Owner (Attorney)");
    }

    public static function calcNewClientsPerAttorneyYearWise($monthYear, $firm_id=0) {
        $data = [];
        $begin = new \DateTime(substr($monthYear->from, 0, 10));
        $end = new \DateTime(substr($monthYear->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $data[] = self::calcNewClientsPerAttorney($i->format("Y-m"), "month", $firm_id);
        }
        return $data;
    }

}
