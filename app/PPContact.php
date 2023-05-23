<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PPContact extends Model
{
    public $timestamps = false;
    protected $table = "pp_contacts";

    public function account () {
        return $this->belongsTo('App\PPAccount', "pp_account_id");
    }

    public static function getIDfromRefID($ref_id, $firm_id) {
        $row = self::select("id")->where("ref_id", $ref_id)->where("firm_id", $firm_id)->first();
        if ($row != null) {
            return $row->id;
        } else {
            return 0;
        }
    }

    public function getDisplayName() {
        return $this->display_name;
    }

    public function getInvoicesTotalOutstanding() {
        return optional($this->account->invoices)->sum("total_outstanding");
    }

    public static function calcPrimaryClients($monthYear, $year = "month", $firm_id=0) {
        $data = self::where("firm_id", $firm_id)->where("is_primary", 1);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereHas("account", function($q) use ($monthYear){
                    $q->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
                });
            } elseif ($year=="month") {
                $data = $data->whereHas("account", function($q) use ($monthYear) {
                    $q->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
                });
            } elseif ($year=="today") {
                $data = $data->whereHas("account", function($q) use ($monthYear) {
                    $q->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
                });
            }
        }
        return $data->count();
    }

    public static function calcNewClientsPerType($monthYear, $year = "month", $firm_id=0, $type="") {
        $data = self::where("firm_id", $firm_id)->where("is_primary", 1);
        $attorneys = PPUser::where("firm_id", $firm_id)->where("type", $type);
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereHas("account", function($q) use ($monthYear){
                    $q->whereRaw("created_at >= '{$monthYear->from}' and created_at <= '{$monthYear->to}'");
                });
                $attorneys = $attorneys->whereRaw("date_of_joining <= '".substr($monthYear->to, 0, 10)."'");
            } elseif ($year=="month") {
                $data = $data->whereHas("account", function($q) use ($monthYear) {
                    $q->whereRaw("LEFT(created_at, 7) = '{$monthYear}'");
                });
                $attorneys = $attorneys->whereRaw("date_of_joining <= '".date("Y-m-t", strtotime($monthYear))."'");
            } elseif ($year=="today") {
                $data = $data->whereHas("account", function($q) use ($monthYear) {
                    $q->whereRaw("LEFT(created_at, 10) = '{$monthYear}'");
                });
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
