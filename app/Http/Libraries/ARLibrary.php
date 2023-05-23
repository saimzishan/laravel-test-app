<?php

namespace App\Http\Libraries;

use App\CLContact;
use App\Definition;
use App\PPAccount;

class ARLibrary
{
    protected $firmid = null;
    protected $firm_integration = null;

    public function __construct($firmid,$firm_integration)
    {
        $this->firmid = $firmid;
        $this->firm_integration = $firm_integration;
        if($this->firmid == null) {
            $this->firmid = HelperLibrary::getFirmID();
        }
    }

    public function getManager () {
        if ($this->firm_integration === "practice_panther") {
            $data = PPAccount::where("firm_id", $this->firmid)
                ->whereHas("invoices", function ($q) {
                    $q->where("invoice_type", "sale");
                    $q->where("total_outstanding", ">", "0");
                });

        } else {
            $data = CLContact::where("firm_id", $this->firmid)
                ->whereHas("invoices", function ($q) {
                    $q->where("due", ">", "0");
                    $q->whereNotIn("state", ["deleted", "void","draft"]);
                });

        }
        $data = $data->get();
        $data->transform(function ($item, $key){
            $item->ar_type = "all";
            return $item;
        });
        return $data;
    }
    public function getCurrent () {
        $definitions = Definition::getInvoicesDefinitions($this->firmid);
        if ($this->firm_integration == "practice_panther") {
            $data = PPAccount::where("firm_id", $this->firmid)
                ->whereHas("invoices", function ($q) use ($definitions) {
                    $q->where("invoice_type", "sale");
                    $q->where("total_outstanding", ">", "0");
                    $q->where(function ($q1) use ($definitions) {
                        $q1->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) >= {$definitions->current_from}");
                        $q1->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) <= {$definitions->current_to}");
                    });
                });
        } else {
            $data = CLContact::where("firm_id", $this->firmid)
                ->whereHas("invoices", function ($q) use ($definitions) {
                    //                   $q->where("type", "Sale");
                    $q->whereNotIn("state", ["deleted", "void","draft"]);
                    $q->where("due", ">", "0");
                    $q->where(function ($q1) use ($definitions) {
                        $q1->whereRaw("DATEDIFF(CURDATE(), cl_invoices.issued_at) >= {$definitions->current_from}");
                        $q1->whereRaw("DATEDIFF(CURDATE(), cl_invoices.issued_at) <= {$definitions->current_to}");
                    });
                });
        }
        $data = $data->get();
        $data->transform(function ($item, $key) {
              $item->ar_type = "current";
               return $item;
           });
        return $data;
    }
    public function getLate () {
        $definitions = Definition::getInvoicesDefinitions($this->firmid);
        if ($this->firm_integration == "practice_panther") {
            $data = PPAccount::where("firm_id", $this->firmid)
                ->whereHas("invoices", function ($q) use ($definitions) {
                    $q->where("invoice_type", "sale");
                    $q->where("total_outstanding", ">", "0");
                    $q->where(function ($q1) use ($definitions) {
                        $q1->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) >= {$definitions->late_from}");
                        $q1->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) <= {$definitions->late_to}");
                    });
                });
        } else {
            $data = CLContact::where("firm_id", $this->firmid)
                ->whereHas("invoices", function ($q) use ($definitions) {
                    $q->whereNotIn("state", ["deleted", "void","draft"]);
                    $q->where("due", ">", "0");
                    $q->where(function ($q1) use ($definitions) {
                        $q1->whereRaw("DATEDIFF(CURDATE(), cl_invoices.issued_at) >= {$definitions->late_from}");
                        $q1->whereRaw("DATEDIFF(CURDATE(), cl_invoices.issued_at) <= {$definitions->late_to}");
                    });
                });
        }
        $data = $data->get();
        $data->transform(function ($item, $key) {
                $item->ar_type = "late";
                return $item;
            });
        return $data;
    }
    public function getDelinquent () {
        $definitions = Definition::getInvoicesDefinitions($this->firmid);
        if ($this->firm_integration == "practice_panther") {
            $data = PPAccount::where("firm_id", $this->firmid)
                ->whereHas("invoices", function ($q) use ($definitions) {
                    $q->where("invoice_type", "sale");
                    $q->where("total_outstanding", ">", "0");
                    $q->where(function ($q1) use ($definitions) {
                        $q1->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) >= {$definitions->delinquent_from}");
                        $q1->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) <= {$definitions->delinquent_to}");
                    });
                });
        } else {
            $data = CLContact::where("firm_id", $this->firmid)
                ->whereHas("invoices", function ($q) use ($definitions) {
                    $q->whereNotIn("state", ["deleted", "void","draft"]);
                    $q->where("due", ">", "0");
                    $q->where(function ($q1) use ($definitions) {
                        $q1->whereRaw("DATEDIFF(CURDATE(), cl_invoices.issued_at) >= {$definitions->delinquent_from}");
                        $q1->whereRaw("DATEDIFF(CURDATE(), cl_invoices.issued_at) <= {$definitions->delinquent_to}");
                    });
                });
        }
        $data = $data->get();
        $data->transform(function ($item, $key) {
            $item->ar_type = "delinquent";
            return $item;
        });
        return $data;
    }
    public function getCollection () {

        $definitions = Definition::getInvoicesDefinitions($this->firmid);
        if ($this->firm_integration == "practice_panther") {
            $data = PPAccount::where("firm_id",$this->firmid)
                ->whereHas("invoices", function ($q) use ($definitions) {
                    $q->where("invoice_type", "sale");
                    $q->where("total_outstanding", ">", "0");
                    $q->where(function ($q1) use ($definitions) {
                        $q1->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) >= {$definitions->collection_from}");
                        $q1->whereRaw("DATEDIFF(CURDATE(), pp_invoices.issue_date) <= {$definitions->collection_to}");
                    });
                });
        } else {
            $data = CLContact::where("firm_id", $this->firmid)
                ->whereHas("invoices", function ($q) use ($definitions) {
                    $q->whereNotIn("state", ["deleted", "void","draft"]);
                    $q->where("due", ">", "0");
                    $q->where(function ($q1) use ($definitions) {
                        $q1->whereRaw("DATEDIFF(CURDATE(), cl_invoices.issued_at) >= {$definitions->collection_from}");
                        $q1->whereRaw("DATEDIFF(CURDATE(), cl_invoices.issued_at) <= {$definitions->collection_to}");
                    });
                });
        }
        $data = $data->get();
        $data->transform(function ($item, $key) {
                $item->ar_type = "collection";
                return $item;
        });
        return $data;
    }
}