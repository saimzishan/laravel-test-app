<?php

namespace App\Http\Libraries;

use App\PPInvoice;
use App\CLInvoice;

class DataLibrary
{

    protected $firm_id = 0;
    protected $firm_integration = "";

    public function __construct($firm_id=null, $firm_integration=null) {
        $this->firm_id = $firm_id;
        $this->firm_integration = $firm_integration;
    }

    public function calcRevenue($monthYear, $year = "month", $firm_id=0, $user="all", $mt="all", $mid=null, $cid=null) {
        $model = $this->firm_integration == "practice_panther" ? "PPInvoice" : "CLInvoice";
        $data = $model::where("firm_id", $firm_id)->select(["total"]);
        if ($this->firm_integration == "practice_panther") {
            $column = "issue_date";
            $data = $data->where('invoice_type','Sale')->where('total', ">", 0);
        } elseif ($this->firm_integration == "clio") {
            $column = "issued_at";
            $data = $data->whereNotIn("state", ["deleted", "void"]);
            if ($user != "all") {
                $data = $data->whereHas("matter.timeEntries", function ($q) use ($user) {
                    $q->where("clio_user_id", $user);
                });
            }
            if ($mt != "all") {
                $data = $data->whereHas("matter", function ($q) use ($mt) {
                    $q->where("matter_type", $mt);
                });
            }
            if ($mid != null) {
                $data = $data->whereHas("matter", function ($q) use ($mid) {
                    $q->where("cl_matters.id", $mid);
                });
            }
            if ($cid != null) {
                $data = $data->whereHas("contact", function ($q) use ($cid) {
                    $q->where("cl_contacts.id", $cid);
                });
            }
        }
        if ($monthYear!="") {
            if ($year=="year") {
                $data = $data->whereRaw("{$column} >= '{$monthYear->from}' and {$column} <= '{$monthYear->to}'");
            } elseif ($year=="month") {
                $data = $data->whereRaw("LEFT({$column}, 7) = '{$monthYear}'");
            } elseif ($year=="today") {
                $data = $data->whereRaw("LEFT({$column}, 10) = '{$monthYear}'");
            }
        }
        return round($data->sum("total"), 2);
    }

}