<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PPAccount extends Model
{
    public $timestamps = false;
    protected  $table = "pp_accounts";

    public function contacts() {
        return $this->hasMany("App\PPContact", "pp_account_id");
    }
    public function matters() {
        return $this->hasMany("App\PPMatter", "pp_account_id");
    }
    public function invoices() {
        return $this->hasMany("App\PPInvoice", "pp_account_id");
    }
    public static function getIDfromRefID($ref_id, $firm_id) {
        $row = self::select("id")->where("ref_id", $ref_id)->where("firm_id", $firm_id)->first();
        if ($row !=null) {
            return $row->id;
        } else {
            return 0;
        }
    }

    public function getDisplayName() {
        return $this->display_name;
    }

    public function getInvoicesTotalOutstanding() {
        if($this->ar_type == "all") {
            return round($this->invoices->sum("total_outstanding"), 2);
        } else {
            $definitions = Definition::getInvoicesDefinitions($this->firm_id);
            if($this->ar_type == "current") {
                return round(optional($this->invoices())
                    ->where("invoice_type", "sale")
                    ->where("total_outstanding", ">", "0")
                ->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) >= {$definitions->current_from}")
                ->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) <= {$definitions->current_to}")
                ->sum("total_outstanding"), 2);
            } elseif ($this->ar_type == "late") {
                return round(optional($this->invoices())
                    ->where("invoice_type", "sale")
                    ->where("total_outstanding", ">", "0")
                    ->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) >= {$definitions->late_from}")
                    ->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) <= {$definitions->late_to}")
                    ->sum("total_outstanding"), 2);
            } elseif ($this->ar_type == "delinquent") {
                return round(optional($this->invoices())
                    ->where("invoice_type", "sale")
                    ->where("total_outstanding", ">", "0")
                    ->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) >= {$definitions->delinquent_from}")
                    ->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) <= {$definitions->delinquent_to}")
                    ->sum("total_outstanding"), 2);
            } elseif ($this->ar_type == "collection") {
                return round(optional($this->invoices())
                    ->where("invoice_type", "sale")
                    ->where("total_outstanding", ">", "0")
                    ->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) >= {$definitions->collection_from}")
                    ->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) <= {$definitions->collection_to}")
                    ->sum("total_outstanding"), 2);
            }
        }
    }
    public function getInvoicesTotal() {
        return round($this->invoices->sum("total"), 2);
    }

}
