<?php

namespace App\Http\Libraries;

use App\CLContact;
use App\CLInvoice;
use App\CLInvoiceLineItem;
use App\CLMatter;
use App\CLPracticeArea;
use App\CLTimeEntry;
use App\CLTask;
use App\CLUser;
use App\CLCredit;
use App\Definition;
use App\Firm;
use App\Http\Resources\ARResource;
use App\PPAccount;
use App\PPContact;
use App\PPExpense;
use App\PPInvoice;
use App\PPInvoiceLineItem;
use App\PPMatter;
use App\PPTimeEntry;
use App\PPTask;
use App\PPUser;
use App\SummaryMatterTracker;
use Carbon\Carbon;
use App\SummaryWrittenOffByEmployee;
use App\SummaryWrittenOffByClient;
class SummaryLibrary
{
    protected $firm;
    protected $fys;
    protected $lib;

    public function __construct(Firm $firm) {
        $this->firm = $firm;
        $this->fys = [
            "this-year"=>Definition::getFinancialYear("this", $this->firm->id),
            "last-year"=>Definition::getFinancialYear("last", $this->firm->id),
            "last-before-year"=>Definition::getFinancialYear("before-last", $this->firm->id),
        ];
        $this->lib = new DataLibrary($this->firm->id, $this->firm->integration);
    }

    public function run($function="") {
        if (empty($function)) {
            $this
                ->financials()
                ->expense()
                ->creditAndRefunds()
                ->productivity()
                ->billables()
                ->contacts()
                ->matters()
                ->financialsMom()
                ->rates()
                ->arAging()
                ->arAgingDetail()
                ->matterTrackerDetail()
                ->matterTracker()
                ->topClients()
                ->topMatters()
                ->aopMatters()
                ->aopClients()
                ->aopGPM()
                ->aopRevenue()
                ->aopOutstandingDues()
                ->writeOffs();


        } else {
            $this->$function();
        }
    }

    private function getRow($model, $value, $column="month") {
        $check = $model::where("firm_id", $this->firm->id)
            ->where($column, $value)->first();
        if ($check!= null) {
            return $check;
        } else {
            return (new $model)->fill(["firm_id"=>$this->firm->id, $column=>$value]);
        }
    }
    private function getRowCustom($model, $value, $column="month",$column1,$value1) {
        $check = $model::where("firm_id", $this->firm->id)
            ->where($column, $value)->where($column1, $value1)->first();
        if ($check!= null) {
            return $check;
        } else {
            return (new $model)->fill(["firm_id"=>$this->firm->id, $column=>$value,$column1=>$value1]);
        }
    }

    private function getRowSimple($model) {
        $check = $model::where("firm_id", $this->firm->id)->first();
        if ($check != null) {
            return $check;
        } else {
            return (new $model)->fill(["firm_id"=>$this->firm->id]);
        }
    }
    private function fillRowSimple($model) {
            return (new $model)->fill(["firm_id"=>$this->firm->id]);
    }

    private function financials() {
        foreach ($this->fys as $fy) {
            $begin = new \DateTime(substr($fy->from, 0, 10));
            $end = new \DateTime(substr($fy->to, 0, 10));
            if ($this->firm->integration == "practice_panther") {
                $old_rev_3 = PPInvoice::calcRevenue((clone $begin)->modify("-3 month")->format("Y-m"), "month", $this->firm->id);
                $old_rev_2 = PPInvoice::calcRevenue((clone $begin)->modify("-2 month")->format("Y-m"), "month", $this->firm->id);
                $old_rev = PPInvoice::calcRevenue((clone $begin)->modify("-1 month")->format("Y-m"), "month", $this->firm->id);
                $old_col = PPInvoice::calcCollectionSimple((clone $begin)->modify("-1 month")->format("Y-m"), "month", $this->firm->id);
            } elseif ($this->firm->integration == "clio") {
                $old_rev_3 = CLInvoice::calcRevenue((clone $begin)->modify("-3 month")->format("Y-m"), "month", $this->firm->id);
                $old_rev_2 = CLInvoice::calcRevenue((clone $begin)->modify("-2 month")->format("Y-m"), "month", $this->firm->id);
                $old_rev = CLInvoice::calcRevenue((clone $begin)->modify("-1 month")->format("Y-m"), "month", $this->firm->id);
                $old_col = CLInvoice::calcCollectionSimple((clone $begin)->modify("-1 month")->format("Y-m"), "month", $this->firm->id);
            }
            $iterator = 0;
            $prev = ["revenue"=>0,"expense"=>0,"collection"=>0];
            for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
                if ($this->firm->integration=="practice_panther") {
                    $rev = PPInvoice::calcRevenue($i->format("Y-m"), "month", $this->firm->id);
                    $col = PPInvoice::calcCollectionSimple($i->format("Y-m"), "month", $this->firm->id);
                    $bil = PPInvoiceLineItem::getTotalBilledHours($i->format("Y-m"), "month", $this->firm->id);
                } else {
                    $rev = CLInvoice::calcRevenue($i->format("Y-m"), "month", $this->firm->id);
                    $col = CLInvoice::calcCollectionSimple($i->format("Y-m"), "month", $this->firm->id);
                    $bil = CLInvoiceLineItem::getTotalBilledHours($i->format("Y-m"), "month", $this->firm->id);
                }
                if ($iterator == 0) {
                    $percentage_growth_revenue = $old_rev == 0 ? 0 : (($rev - $old_rev) / $old_rev) * 100;
                    $percentage_growth_collection = $old_col == 0 ? 0 : (($col - $old_col) / $old_col) * 100;
                } else {
                    $percentage_growth_revenue = $prev['revenue'] == 0 ? 0 : (($rev - $prev['revenue']) / $prev['revenue']) * 100;
                    $percentage_growth_collection = $prev['collection'] == 0 ? 0 : (($col - $prev['collection']) / $prev['collection']) * 100;
                }
                $forecast = ($old_rev_3 + $old_rev_2 + $old_rev) / 3;
                $old_rev_3 = $old_rev_2;
                $old_rev_2 = $old_rev;
                $old_rev = $rev;
                $avg_rate = $bil != 0 ? round($rev / $bil, 0) : 0;
                $avg_rate_forecast = $bil != 0 ? round($forecast / $bil, 0) : 0;
                $prev = [
                    "revenue"=>round($rev, 0),
                    "collection"=>round($col, 0),
                    "billed_time"=>round($bil, 0),
                    "revenue_percentage_growth" => round($percentage_growth_revenue, 0),
                    "collection_percentage_growth" => round($percentage_growth_collection, 0),
                    "revenue_avg_rate" => $avg_rate,
                    "revenue_forecast" => round($forecast, 0),
                    "revenue_actual_vs_forecast" => round($rev - $forecast, 0),
                    "revenue_avg_rate_forecast" => $avg_rate_forecast,
                    "revenue_avg_rate_actual_vs_forecast" => round($avg_rate - $avg_rate_forecast, 0),
                ];
                $this->getRow("\\App\\SummaryMonth", $i->format("Y-m-01"))
                    ->fill($prev)
                    ->save();
                $iterator++;
            }
        }
        return $this;
    }

    private function creditAndRefunds() {
        foreach ($this->fys as $fy) {
            $begin = new \DateTime(substr($fy->from, 0, 10));
            $end = new \DateTime(substr($fy->to, 0, 10));
            for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
                if ($this->firm->integration=="practice_panther") {
                    $refunds = PPInvoice::calcRefund($i->format("Y-m"), "month", $this->firm->id);
                    $credits = PPInvoice::calcCredit($i->format("Y-m"), "month", $this->firm->id);
                } else {
                    $refunds = 0;
                    $credits = CLCredit::calcCredit($i->format("Y-m"), "month", $this->firm->id);
                }
                $this->getRow("\\App\\SummaryMonth", $i->format("Y-m-01"))
                    ->fill([
                        "refunds"=>$refunds,
                        "credits"=>$credits,
                    ])
                    ->save();
            }
        }
        return $this;
    }

    private function productivity() {
        foreach ($this->fys as $fy) {
            $begin = new \DateTime(substr($fy->from, 0, 10));
            $end = new \DateTime(substr($fy->to, 0, 10));
            if ($this->firm->integration == "practice_panther") {
                $old_bil_3 = PPInvoiceLineItem::getTotalBilledHours((new Carbon($fy->from))->subMonths(3)->format("Y-m"), "month", $this->firm->id);
                $old_bil_2 = PPInvoiceLineItem::getTotalBilledHours((new Carbon($fy->from))->subMonths(2)->format("Y-m"), "month", $this->firm->id);
                $old_bil = PPInvoiceLineItem::getTotalBilledHours((new Carbon($fy->from))->subMonths(1)->format("Y-m"), "month", $this->firm->id);
            } elseif ($this->firm->integration == "clio") {
                $old_bil_3 = CLInvoiceLineItem::getTotalBilledHours((new Carbon($fy->from))->subMonths(3)->format("Y-m"), "month", $this->firm->id);
                $old_bil_2 = CLInvoiceLineItem::getTotalBilledHours((new Carbon($fy->from))->subMonths(2)->format("Y-m"), "month", $this->firm->id);
                $old_bil = CLInvoiceLineItem::getTotalBilledHours((new Carbon($fy->from))->subMonths(1)->format("Y-m"), "month", $this->firm->id);
            }
            for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
                if ($this->firm->integration=="practice_panther") {
                    $ava = PPUser::calcAvailableHour($i->format("Y-m"), "month", $this->firm->id);
                    $wor = PPTimeEntry::getTotalBillableHoursUtilization($i->format("Y-m"), "month", $this->firm->id);
                    $bil = PPInvoiceLineItem::getTotalBilledHours($i->format("Y-m"), "month", $this->firm->id);
                    $col = PPInvoice::calcTotalCollectedHoursAverage($i->format("Y-m"), "month", $this->firm->id);
                } else {
                    $ava = CLUser::calcAvailableHour($i->format("Y-m"), "month", $this->firm->id, "all", $this->firm->getCurrentPackage());
                    $wor = CLTimeEntry::getTotalBillableHoursUtilization($i->format("Y-m"), "month", $this->firm->id);
                    $col = CLInvoice::calcTotalCollectedHoursAverage($i->format("Y-m"), "month", $this->firm->id);
                    $bil = CLInvoiceLineItem::getTotalBilledHours($i->format("Y-m"), "month", $this->firm->id);
                }
                if ($bil != 0) {
                    $billed_vs_collection = round(($col / $bil) * 100, 0);
                } else {
                    $billed_vs_collection = 0;
                }
                $forecast = ($old_bil_3 + $old_bil_2 + $old_bil) / 3;
                $old_rev_3 = $old_bil_2;
                $old_rev_2 = $old_bil;
                $old_rev = $bil;
                $this->getRow("\\App\\SummaryMonth", $i->format("Y-m-01"))
                    ->fill([
                        "available_time" => round($ava, 0),
                        "worked_time" => round($wor, 0),
                        "collected_time" => round($col, 0),
                        "billed_vs_collected" => $billed_vs_collection,
                        "billed_time_forecast" => round($forecast, 0),
                        "billed_actual_vs_forecast" => round($bil - $forecast, 1),
                    ])
                    ->save();
            }
        }
        return $this;
    }

    private function billables() {
        foreach ($this->fys as $fy) {
            $begin = new \DateTime(substr($fy->from, 0, 10));
            $end = new \DateTime(substr($fy->to, 0, 10));
            for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
                if ($this->firm->integration=="practice_panther") {
                    $billable_hours = PPTimeEntry::getTotalBillableHours($i->format("Y-m"), "month", $this->firm->id);
                    $billed_hours = PPTimeEntry::getTotalBilledHours($i->format("Y-m"), "month", $this->firm->id);
                } else {
                    $billable_hours = CLTimeEntry::getTotalBillableHours($i->format("Y-m"), "month", $this->firm->id);
                    $billed_hours = CLInvoiceLineItem::getTotalBilledHours($i->format("Y-m"), "month", $this->firm->id);
                }
                $this->getRow("\\App\\SummaryMonth", $i->format("Y-m-01"))
                    ->fill([
                        "billable_hours"=>round($billable_hours, 0),
                        "billed_hours"=>round($billed_hours, 0),
                    ])
                    ->save();
            }
        }
        return $this;
    }

    private function contacts() {
        foreach ($this->fys as $fy) {
            $begin = new \DateTime(substr($fy->from, 0, 10));
            $end = new \DateTime(substr($fy->to, 0, 10));
            for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
                $last_from = date("Y-m-01", strtotime($i->format("Y-m-01") . ' - 1 month'));
                $last_to = date("Y-m-t", strtotime($i->format("Y-m-01") . ' - 1 month'));
                if ($this->firm->integration=="practice_panther") {
                    $clients = PPContact::where("firm_id", $this->firm->id)
                        ->where("is_primary", 1)
                        ->whereHas("account", function($q) use ($i){
                            $q->whereRaw("created_at >= '{$i->format('Y-m-01')}' and created_at <= '{$i->format('Y-m-t')}'");
                        })->count();
                    $last_clients = PPContact::where("firm_id", $this->firm->id)
                        ->where("is_primary", 1)
                        ->whereHas("account", function ($q) use ($last_from, $last_to) {
                            $q->whereRaw("created_at >= '{$last_from}' and created_at <= '{$last_to}'");
                        })->count();
                } else {
                    $clients = CLContact::where("firm_id", $this->firm->id)
                        ->where("is_client", 1)
                        ->whereRaw("created_at >= '{$i->format('Y-m-01')}' and created_at <= '{$i->format('Y-m-t')}'")->count();
                    $last_clients = CLContact::where("firm_id", $this->firm->id)
                        ->where("is_client", 1)
                        ->whereRaw("created_at >= '{$last_from}' and created_at <= '{$last_to}'")->count();
                }
                if ($last_clients != 0) {
                    $clients_mom = round((($clients - $last_clients) / $last_clients) * 100, 0);
                } else {
                    $clients_mom = 0;
                }
                $this->getRow("\\App\\SummaryMonth", $i->format("Y-m-01"))
                    ->fill([
                        "new_clients"=>$clients,
                        "clients_mom"=>$clients_mom,
                    ])
                    ->save();
            }
        }
        return $this;
    }

    private function matters() {
        foreach ($this->fys as $fy) {
            $begin = new \DateTime(substr($fy->from, 0, 10));
            $end = new \DateTime(substr($fy->to, 0, 10));
            for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
                $last_from = date("Y-m-01", strtotime($i->format("Y-m-01") . ' - 1 month'));
                $last_to = date("Y-m-t", strtotime($i->format("Y-m-01") . ' - 1 month'));
                if ($this->firm->integration=="practice_panther") {
                    $matters = PPMatter::where("firm_id", $this->firm->id)
                        ->whereRaw("created_at >= '{$i->format('Y-m-01')}' and created_at <= '{$i->format('Y-m-t')}'")->count();
                    $last_matters = PPMatter::where("firm_id", $this->firm->id)
                        ->whereRaw("created_at >= '{$last_from}' and created_at <= '{$last_to}'")->count();
                } else {
                    $matters = CLMatter::where("firm_id", $this->firm->id)
                        ->whereRaw("created_at >= '{$i->format('Y-m-01')}' and created_at <= '{$i->format('Y-m-t')}'")->count();
                    $last_matters = CLMatter::where("firm_id", $this->firm->id)
                        ->whereRaw("created_at >= '{$last_from}' and created_at <= '{$last_to}'")->count();
                }
                if ($last_matters != 0) {
                    $matter_mom = round((($matters - $last_matters) / $last_matters) * 100, 0);
                } else {
                    $matter_mom = 0;
                }
                $this->getRow("\\App\\SummaryMonth", $i->format("Y-m-01"))
                    ->fill([
                        "new_matters"=>$matters,
                        "matters_mom"=>$matter_mom,
                    ])
                    ->save();
            }
        }
        return $this;
    }

    private function financialsMom() {
        foreach ($this->fys as $fy) {
            $begin = new \DateTime(substr($fy->from, 0, 10));
            $end = new \DateTime(substr($fy->to, 0, 10));
            for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
                if ($this->firm->integration=="practice_panther") {
                    $this_rev = PPInvoice::calcRevenue(date("Y-m", strtotime($i->format("Y-m-01"))), "month", $this->firm->id);
                    $last_rev = PPInvoice::calcRevenue(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id);
                    $this_exp = PPExpense::calcExpense(date("Y-m", strtotime($i->format("Y-m-01"))), "month", $this->firm->id, "all", "all", null, $this->firm->getCurrentPackage());
                    $last_exp = PPExpense::calcExpense(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, "all", "all", null, $this->firm->getCurrentPackage());
                    $this_col = PPInvoice::calcCollectionSimple(date("Y-m", strtotime($i->format("Y-m-01"))), "month", $this->firm->id);
                    $last_col = PPInvoice::calcCollectionSimple(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id);
                } else {
                    $this_rev = CLInvoice::calcRevenue(date("Y-m", strtotime($i->format("Y-m-01"))), "month", $this->firm->id);
                    $last_rev = CLInvoice::calcRevenue(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id);
                    $this_exp = CLTimeEntry::calcExpense(date("Y-m", strtotime($i->format("Y-m-01"))), "month", $this->firm->id, "all", "all", null, $this->firm->getCurrentPackage());
                    $last_exp = CLTimeEntry::calcExpense(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, "all", "all", null, $this->firm->getCurrentPackage());
                    $this_col = CLInvoice::calcCollectionSimple(date("Y-m", strtotime($i->format("Y-m-01"))), "month", $this->firm->id);
                    $last_col = CLInvoice::calcCollectionSimple(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id);
                }
                $this->getRow("\\App\\SummaryMonth", $i->format("Y-m-01"))
                    ->fill([
                        "revenue_mom"=>round(($last_rev == 0 ? 0 :($this_rev - $last_rev) / $last_rev) * 100, 0),
                        "expense_mom"=>round(($last_exp == 0 ? 0 :($this_exp - $last_exp) / $last_exp) * 100, 0),
                        "collection_mom"=>round(($last_col == 0 ? 0 :($this_col - $last_col) / $last_col) * 100, 0),
                    ])
                    ->save();
            }
        }
        return $this;
    }

    private function rates() {
        foreach ($this->fys as $fy) {
            $begin = new \DateTime(substr($fy->from, 0, 10));
            $end = new \DateTime(substr($fy->to, 0, 10));
            for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
                if ($this->firm->integration=="practice_panther") {
                    $uti = PPTimeEntry::calcUtilization($i->format("Y-m"), "month", $this->firm->id);
                    $rea = PPTimeEntry::calcRealization($i->format("Y-m"), "month", $this->firm->id);
                    $col = PPInvoice::calcCollection($i->format("Y-m"), "month", $this->firm->id);
                } else {
                    $uti = CLTimeEntry::calcUtilization($i->format("Y-m"), "month", $this->firm->id);
                    $rea = CLTimeEntry::calcRealization($i->format("Y-m"), "month", $this->firm->id);
                    $col = CLInvoice::calcCollection($i->format("Y-m"), "month", $this->firm->id);
                }
                $this->getRow("\\App\\SummaryMonth", $i->format("Y-m-01"))
                    ->fill([
                        "utilization_rate"=>$uti,
                        "realization_rate"=>$rea,
                        "collection_rate"=>$col,
                    ])
                    ->save();
            }
        }
        return $this;
    }

    private function arAging() {
        $definitions = Definition::getInvoicesDefinitions($this->firm->id);
        if ($this->firm->integration=="practice_panther") {
            $time_field = "issue_date";
            $balance_field = "total_outstanding";
            $invoices = PPInvoice::where("firm_id", $this->firm->id)->where("invoice_type", "sale")
                ->where("total_outstanding", ">", "0");
        } else {
            $time_field = "issued_at";
            $balance_field = "due";
            $invoices = CLInvoice::where("firm_id", $this->firm->id)
                ->whereNotIn("state", ["deleted", "void","draft"])
                ->where("due", ">", "0");
        }
        $cur = 0;
        $lat = 0;
        $del = 0;
        $col = 0;
        foreach ($invoices->get() as $inv) {
            $cd = new \DateTime();
            $id = new \DateTime(substr($inv->{$time_field}, 0, 10));
            $diff = $id->diff($cd)->format("%a");
            if ($diff >= $definitions->current_from && $diff <= $definitions->current_to) {
                $cur += round($inv->{$balance_field}, 0);
            } elseif ($diff >= $definitions->late_from && $diff <= $definitions->late_to) {
                $lat += round($inv->{$balance_field}, 0);
            } elseif ($diff >= $definitions->delinquent_from && $diff <= $definitions->delinquent_to) {
                $del += round($inv->{$balance_field}, 0);
            } elseif ($diff >= $definitions->collection_from && $diff <= $definitions->collection_to) {
                $col += round($inv->{$balance_field}, 0);
            }
        }
        $this->getRowSimple("\\App\\SummaryAllTime")
            ->fill([
                "ar_current"=>$cur,
                "ar_late"=>$lat,
                "ar_delinquent"=>$del,
                "ar_collection"=>$col,
            ])
            ->save();
        $this->fillRowSimple("\\App\\SummaryARHistory")
            ->fill([
                "ar_current"=>$cur,
                "ar_late"=>$lat,
                "ar_delinquent"=>$del,
                "ar_collection"=>$col,
                "ar_total"=>$cur+$lat+$del+$col,
            ])
            ->save();

        return $this;
    }
    private function arAgingDetail() {

        $obj = new ARLibrary($this->firm->id,$this->firm->integration);
        $current = $this->getResult($obj->getCurrent());;
        $late = $this->getResult($obj->getLate());
        $delinquent = $this->getResult($obj->getDelinquent());
        $collection = $this->getResult($obj->getCollection());
        $manager = $this->getResult($obj->getManager());
        foreach ($current as $cur) {
            $this->getRowCustom("\\App\\SummaryAR",$cur->contact_id,"contact_id","type","current")
                ->fill([
                    "contact_name"=>$cur->contact_name,
                    "total"=>$cur->total,
                    "outstanding"=>$cur->outstanding,
                    "percentage_to_sale"=>$cur->percentage_to_sale,
                    "percentage_to_outstanding"=>$cur->percentage_to_outstanding,
                ])
                ->save();
        }
        foreach ($late as $lat) {
            $this->getRowCustom("\\App\\SummaryAR",$lat->contact_id,"contact_id","type","late")
                ->fill([
                    "contact_name"=>$lat->contact_name,
                    "total"=>$lat->total,
                    "outstanding"=>$lat->outstanding,
                    "percentage_to_sale"=>$lat->percentage_to_sale,
                    "percentage_to_outstanding"=>$lat->percentage_to_outstanding,
                ])
                ->save();
        }
        foreach ($delinquent as $del) {
            $this->getRowCustom("\\App\\SummaryAR",$del->contact_id,"contact_id","type","delinquent")
                ->fill([
                    "contact_name"=>$del->contact_name,
                    "total"=>$del->total,
                    "outstanding"=>$del->outstanding,
                    "percentage_to_sale"=>$del->percentage_to_sale,
                    "percentage_to_outstanding"=>$del->percentage_to_outstanding,
                ])
                ->save();
        }
        foreach ($collection as $col) {
            $this->getRowCustom("\\App\\SummaryAR",$col->contact_id,"contact_id","type","collection")
                ->fill([
                    "contact_name"=>$col->contact_name,
                    "total"=>$col->total,
                    "outstanding"=>$col->outstanding,
                    "percentage_to_sale"=>$col->percentage_to_sale,
                    "percentage_to_outstanding"=>$col->percentage_to_outstanding,
                ])
                ->save();
        }
        foreach ($manager as $man) {
            $this->getRowCustom("\\App\\SummaryAR",$man->contact_id,"contact_id","type","all")
                ->fill([
                    "contact_name"=>$man->contact_name,
                    "total"=>$man->total,
                    "outstanding"=>$man->outstanding,
                    "percentage_to_sale"=>$man->percentage_to_sale,
                    "percentage_to_outstanding"=>$man->percentage_to_outstanding,
                ])
                ->save();
        }


        return $this;
    }
    private function matterTracker () {
        $red = SummaryMatterTracker::where("firm_id", $this->firm->id)->where("type", "red")->count();
        $yellow = SummaryMatterTracker::where("firm_id", $this->firm->id)->where("type", "yellow")->count();
        $green = SummaryMatterTracker::where("firm_id", $this->firm->id)->where("type", "green")->count();
        $this->getRowSimple("\\App\\SummaryAllTime")
            ->fill([
                "matters_red"=>$red,
                "matters_yellow"=>$yellow,
                "matters_green"=>$green,
            ])
            ->save();
        return $this;
    }

    private function matterTrackerDetail () {
        $obj = new MatterLibrary($this->firm->id, $this->firm->integration, $this->firm->getCurrentPackage());
        $me = $this;
        $obj->green("all", "all", function($d) use ($me) {
            $me->getRow("\\App\\SummaryMatterTracker", $d->ref_id, "matter_id")
                ->fill([
                    "matter_id" => $d->ref_id,
                    "matter_name" => $d->getName(),
                    "activities" => $d->getActivitiesCount(),
                    "time_entries" => $d->getTimeEntriesCount(),
                    "invoices" => $d->getInvoicesCount(),
                    "days_file_open" => $d->getDaysFileOpen(),
                    "created_date" => $d->getCreatedDate(),
                    "created_date_raw" => $d->getCreatedDateRaw(),
                    "type" => "green"
                ])
                ->save();
        });
        $obj->yellow("all", "all", function($d) use ($me) {
            $me->getRow("\\App\\SummaryMatterTracker", $d->ref_id, "matter_id")
                ->fill([
                    "matter_id" => $d->ref_id,
                    "matter_name" => $d->getName(),
                    "activities" => $d->getActivitiesCount(),
                    "time_entries" => $d->getTimeEntriesCount(),
                    "invoices" => $d->getInvoicesCount(),
                    "days_file_open" => $d->getDaysFileOpen(),
                    "created_date" => $d->getCreatedDate(),
                    "created_date_raw" => $d->getCreatedDateRaw(),
                    "type" => "yellow"
                ])
                ->save();
        });
        $obj->red("all", "all", function($d) use ($me) {
            $me->getRow("\\App\\SummaryMatterTracker", $d->ref_id, "matter_id")
                ->fill([
                    "matter_id" => $d->ref_id,
                    "matter_name" => $d->getName(),
                    "activities" => $d->getActivitiesCount(),
                    "time_entries" => $d->getTimeEntriesCount(),
                    "invoices" => $d->getInvoicesCount(),
                    "days_file_open" => $d->getDaysFileOpen(),
                    "created_date" => $d->getCreatedDate(),
                    "created_date_raw" => $d->getCreatedDateRaw(),
                    "type" => "red"
                ])
                ->save();
        });
        return $this;
    }

    private function topClients () {
        if ($this->firm->integration == "practice_panther") {
            $raw = PPContact::where("firm_id", $this->firm->id)->where("is_primary", true)->get();
        } else {
            $raw = CLContact::where("firm_id", $this->firm->id)->where("is_client", 1)->get();
        }
        $data = [];
        $me = $this;
        foreach ($raw as $v) {
            if ($v->getDisplayName() != null) {
                if ($this->firm->integration == "practice_panther") {
                    $total = optional($v->account);
                    if($total->invoices !=null) {
                        $total= optional($total->invoices)->where("invoice_type", "Sale")->sum("total");
                    } else {
                        $total = 0;
                    }
                    $outstanding = optional($v->account);
                    if($outstanding->invoices != null) {
                        $outstanding = optional($outstanding)->invoices->where("invoice_type", "Sale")->sum("total_outstanding");
                    } else {
                        $outstanding = 0;
                    }
                } else {
                    $total = $v->invoices->sum("total");
                    $outstanding = $v->invoices->sum("due");
                }
                $data[] = [
                    "id"=> $v->ref_id,
                    "name"=> $v->getDisplayName(),
                    "total"=> round($total, 2),
                    "outstanding"=> round($outstanding, 2),
                ];
            }
        }
        $tot = collect($data)->sortByDesc('total');
        $out = collect($data)->sortByDesc('outstanding');
        $tot->values()->take(10)->each(function ($item) use ($me) {
            $me->getRow("\\App\\SummaryClient", $item['id'], "client_id")
                ->fill([
                    "client_id" => $item['id'],
                    "client_name" => $item['name'],
                    "revenue" => $item['total'],
                ])
                ->save();
        });
        $out->values()->take(10)->each(function ($item) use ($me) {
            $me->getRow("\\App\\SummaryClient", $item['id'], "client_id")
                ->fill([
                    "client_id" => $item['id'],
                    "client_name" => $item['name'],
                    "outstanding_dues" => $item['outstanding'],
                ])
                ->save();
        });
        return $this;
    }

    private function topMatters () {
        if ($this->firm->integration == "practice_panther") {
            $raw = PPMatter::where("firm_id", $this->firm->id)->get();
        } else {
            $raw = CLMatter::where("firm_id", $this->firm->id)->get();
        }
        $data = [];
        $me = $this;
        foreach ($raw as $v) {
            if ($v->getName() != null) {
                if ($this->firm->integration == "practice_panther") {
                    $data[] = [
                        "id" => $v->ref_id,
                        "name" => $v->getName(),
                        "revenue" => round($v->invoices->where("invoice_type", "Sale")->sum("total"), 2),
                        "outstanding" => round($v->invoices->where("invoice_type", "Sale")->sum("total_outstanding"), 2),
                    ];
                } else {
                    $data[] = [
                        "id" => $v->ref_id,
                        "name" => $v->getName(),
                        "revenue" => round($v->invoices->sum("total"), 2),
                        "outstanding" => round($v->invoices->sum("due"), 2)
                    ];
                }
            }
        }
        $tot = collect($data)->sortByDesc('revenue');
        $out = collect($data)->sortByDesc('outstanding');
        $tot->values()->take(10)->each(function ($item) use ($me) {
            $me->getRow("\\App\\SummaryMatter", $item['id'], "matter_id")
                ->fill([
                    "matter_id" => $item['id'],
                    "matter_name" => $item['name'],
                    "revenue" => $item['revenue'],
                ])
                ->save();
        });
        $out->values()->take(10)->each(function ($item) use ($me) {
            $me->getRow("\\App\\SummaryMatter", $item['id'], "matter_id")
                ->fill([
                    "matter_id" => $item['id'],
                    "matter_name" => $item['name'],
                    "outstanding_dues" => $item['outstanding'],
                ])
                ->save();
        });
        return $this;
    }

    private function aopMatters () {
        $me = $this;
        if ($this->firm->integration == "practice_panther") {
            $total = PPMatter::where("firm_id", $this->firm->id)
                ->whereRaw("created_at >= '{$this->fys['this-year']->from}' and created_at <= '{$this->fys['this-year']->to}'")
                ->count();
            $data = PPMatter::calcNewMattersPerAOP($this->fys['this-year'], 'year', $this->firm->id);
        } else {
            $total = CLMatter::where("firm_id", $this->firm->id)
                ->whereRaw("created_at >= '{$this->fys['this-year']->from}' and created_at <= '{$this->fys['this-year']->to}'")
                ->count();
            $data = CLMatter::calcNewMattersPerAOP($this->fys['this-year'], 'year', $this->firm->id);
        }
        $data->each(function ($item, $key) use ($total, $me) {
            $me->getRow("\\App\\SummaryAOP", $key, "name")
                ->fill([
                    "name" => $key,
                    "matters" => round(($item / $total) * 100, 0),
                ])
                ->save();
        });
        return $this;
    }

    private function aopClients () {
        if ($this->firm->integration == "practice_panther") {
            $raw = PPAccount::where("firm_id", $this->firm->id)
                ->whereRaw("created_at >= '{$this->fys['this-year']->from}' and created_at <= '{$this->fys['this-year']->to}'")
                ->whereHas("matters");
            $total = (clone $raw)->count();
            $mts = PPMatter::where("firm_id", $this->firm->id)
                ->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
            foreach ($mts as $mt) {
                $row = (clone $raw)->whereHas("matters", function($q) use ($mt) {
                    $q->where("matter_type", $mt->matter_type);
                });
                $client = 0;
                if($total ==0){
                    $client = 0;
                } else {
                    $client = round(($row->count() / $total) * 100);
                }
                $this->getRow("\\App\\SummaryAOP", $mt->matter_type, "name")
                    ->fill([
                        "name" => $mt->matter_type,
                        "clients" => $client,
                    ])
                    ->save();
            }
        } else {
            $raw = CLContact::where("firm_id", $this->firm->id)
                ->whereRaw("created_at >= '{$this->fys['this-year']->from}' and created_at <= '{$this->fys['this-year']->to}'")
                ->whereHas("matters");
            $total = (clone $raw)->count();
            $mts = CLPracticeArea::where("firm_id", $this->firm->id)
                ->where("name", "<>", null)->select('name')->get();
            foreach ($mts as $mt) {
                $row = (clone $raw)->whereHas("matters", function($q) use ($mt) {
                    $q->where("matter_type", $mt->name);
                });
                $client = 0;
                if($total ==0){
                    $client = 0;
                } else {
                    $client = round(($row->count() / $total) * 100);
                }
                $this->getRow("\\App\\SummaryAOP", $mt->name, "name")
                    ->fill([
                        "name" => $mt->name,
                        "clients" => $client,
                    ])
                    ->save();
            }
        }
        return $this;
    }

    private function aopGPM() {
        $begin = new \DateTime(substr($this->fys['this-year']->from, 0, 10));
        $end = new \DateTime(substr($this->fys['this-year']->to, 0, 10));
        $arr = [];
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if ($this->firm->integration=="practice_panther") {
                $tmp = PPMatter::calcAOPPerGPM($i->format("Y-m"), "month", $this->firm->id);
            } else {
                $tmp = CLMatter::calcAOPPerGPM($i->format("Y-m"), "month", $this->firm->id);
            }
            foreach ($tmp as $key => $value) {
                if (isset($arr[$key])) {
                    $arr[$key] += round($value, 2);
                } else {
                    $arr[$key] = round($value, 2);
                }
                $arr[$key] = round($arr[$key], 2);
            }
        }
        collect($arr)->each(function($item, $key){
            $this->getRow("\\App\\SummaryAOP", $key, "name")
                ->fill([
                    "name" => $key,
                    "gross_profit_margin" => round($item, 0),
                ])
                ->save();
        });
        return $this;
    }

    private function aopRevenue() {
        $begin = new \DateTime(substr($this->fys['this-year']->from, 0, 10));
        $end = new \DateTime(substr($this->fys['this-year']->to, 0, 10));
        $arr = [];
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if ($this->firm->integration=="practice_panther") {
                $tmp = PPMatter::calcAOPPerRevenue($i->format("Y-m"), "month", $this->firm->id);
            } else {
                $tmp = CLMatter::calcAOPPerRevenue($i->format("Y-m"), "month", $this->firm->id);
            }
            foreach ($tmp as $key => $value) {
                if (isset($arr[$key])) {
                    $arr[$key] += round($value, 2);
                } else {
                    $arr[$key] = round($value, 2);
                }
                $arr[$key] = round($arr[$key], 2);
            }
        }
        collect($arr)->each(function($item, $key){
            $this->getRow("\\App\\SummaryAOP", $key, "name")
                ->fill([
                    "name" => $key,
                    "revenue" => round($item, 0),
                ])
                ->save();
        });
        return $this;
    }

    private function aopOutstandingDues() {
        $begin = new \DateTime(substr($this->fys['this-year']->from, 0, 10));
        $end = new \DateTime(substr($this->fys['this-year']->to, 0, 10));
        $arr = [];
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if ($this->firm->integration=="practice_panther") {
                $tmp = PPMatter::calcTop5AOPPerOutstandingDues($i->format("Y-m"), "month", $this->firm->id);
            } else {
                $tmp = CLMatter::calcTop5AOPPerOutstandingDues($i->format("Y-m"), "month", $this->firm->id);
            }
            foreach ($tmp as $key => $value) {
                if (isset($arr[$key])) {
                    $arr[$key] += round($value, 2);
                } else {
                    $arr[$key] = round($value, 2);
                }
                $arr[$key] = round($arr[$key], 2);
            }
        }
        collect($arr)->each(function($item, $key){
            $this->getRow("\\App\\SummaryAOP", $key, "name")
                ->fill([
                    "name" => $key,
                    "outstanding_dues" => round($item, 0),
                ])
                ->save();
        });
        return $this;
    }
    private function expense() {
        foreach ($this->fys as $fy) {
            $begin = new \DateTime(substr($fy->from, 0, 10));
            $end = new \DateTime(substr($fy->to, 0, 10));
            if ($this->firm->integration == "practice_panther") {
                $old_exp = PPExpense::calcExpense((clone $begin)->modify("-1 month")->format("Y-m"), "month", $this->firm->id, "all", "all", null, $this->firm->getCurrentPackage());
            } elseif ($this->firm->integration == "clio") {
                $old_exp = CLTimeEntry::calcExpense((clone $begin)->modify("-1 month")->format("Y-m"), "month", $this->firm->id, "all", "all", null, $this->firm->getCurrentPackage());
            }
            $iterator = 0;
            $prev = ["expense"=>0];
            for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
                if ($this->firm->integration=="practice_panther") {
                    $exp = PPExpense::calcExpense($i->format("Y-m"), "month", $this->firm->id, "all", "all", null, $this->firm->getCurrentPackage());
                    $rev = PPInvoice::calcRevenue($i->format("Y-m"), "month", $this->firm->id);
                } else {
                    $exp = CLTimeEntry::calcExpense($i->format("Y-m"), "month", $this->firm->id, "all", "all", null, $this->firm->getCurrentPackage());
                    $rev = CLInvoice::calcRevenue($i->format("Y-m"), "month", $this->firm->id);
                }
                if ($iterator == 0) {
                    $percentage_growth_expense = $old_exp == 0 ? 0 : (($exp - $old_exp) / $old_exp) * 100;
                } else {
                    $percentage_growth_expense = $prev['expense'] == 0 ? 0 : (($exp - $prev['expense']) / $prev['expense']) * 100;
                }
                $prev = [
                    "expense"=>round($exp, 0),
                    "expense_percentage_growth" => round($percentage_growth_expense, 0),
                    "overall_gross_profit_margin" => $rev - $exp
                ];
                $this->getRow("\\App\\SummaryMonth", $i->format("Y-m-01"))
                    ->fill($prev)
                    ->save();
                $iterator++;
            }
        }
        return $this;
    }

    public function allAOP() {
        $this->aopMatters();
        $this->aopClients();
        $this->aopGPM();
        $this->aopRevenue();
        $this->aopOutstandingDues();
    }
    public function employeeWrittenOff() {
        if ($this->firm->integration == "practice_panther") {
            return true;
        }else {
            $users = CLUser::where("firm_id", $this->firm->id)->where("enabled", 1)->get();
            foreach ($users as $user) {
                $matters = $user->matters;
                $totalhours = 0;
                $deletedhours = 0;
                $deletedhoursmanually = 0;
                $billedhours = 0;
                $totalhoursamount = 0;
                $deletedhoursamount = 0;
                $deletedhoursmanuallyamount = 0;
                $billedhoursamount = 0;
                $postbillamount = 0;
                foreach ($matters as $matter) {
                   // $totalhours =  $matter->timeEntries->where("flat_rate",0)->sum("quantity_in_hours");
                    $billedinvoices = $matter->invoicesNotDeleted->whereIn("state", ["awaiting_payment", "paid"]);
                    $postbillinvoices = $matter->invoicesNotDeleted->where("state", "void");
                    $deletedinvoices = $matter->invoicesNotDeleted->where("state", "deleted");
                    foreach ($matter->invoicesNotDeleted as $invoice) {
                        $totalhours = $totalhours + $invoice->invoicelineitem->where("type", "ActivityLineItem")->where("kind", "Service")->sum("quantity");
                        $totalhoursamount = $totalhoursamount + $invoice->invoicelineitem->where("type", "ActivityLineItem")->where("kind", "Service")->sum("total");
                        $lineitems = $invoice->invoicelineitem->where("type", "ActivityLineItem")->where("kind", "Service");
                        foreach ($lineitems as $item) {
                            $time_Entry = null;
                            $time_Entry = optional($item->timeEntry)->quantity_in_hours;
                            $item_hours = $item->quantity;
                            $price = optional($item->timeEntry)->price;
                            if ($time_Entry != null) {
                                //dump($time_Entry);
                                $diff = $time_Entry - $item_hours;
                                $deletedhoursmanually = $deletedhoursmanually + $diff;
                                $amount = $price * $diff;
                                $deletedhoursmanuallyamount = $deletedhoursmanuallyamount + $amount;
                            }

                        }
                    }
                    foreach ($billedinvoices as $invoice1) {
                        $billedhours = $billedhours + $invoice1->invoicelineitem->where("type", "ActivityLineItem")->where("kind", "Service")->sum("quantity");
                        $billedhoursamount = $billedhoursamount + $invoice1->invoicelineitem->where("type", "ActivityLineItem")->where("kind", "Service")->sum("total");
                    }
                    foreach ($deletedinvoices as $invoice2) {
                        $deletedhours = $deletedhours + $invoice2->invoicelineitem->where("type", "ActivityLineItem")->where("kind", "Service")->sum("quantity");
                        $deletedhoursamount = $deletedhoursamount + $invoice2->invoicelineitem->where("type", "ActivityLineItem")->where("kind", "Service")->sum("total");
                    }
                    foreach ($postbillinvoices as $invoice3) {
                        $postbillamount = $postbillamount + $invoice3->invoicelineitem->where("type", "ActivityLineItem")->where("kind", "Service")->sum("total");
                    }

                }
                $this->getRow("\\App\\SummaryWrittenOffByEmployee", $user->name, "employee_name")
                    ->fill([
                        "total_hours" => round($totalhours, 2),
                        "written_off_hours" => round($deletedhours, 2),
                        "manually_written_off_hours" => round($deletedhoursmanually, 2),
                        "billed_hours" => round($billedhours, 2),
                        "total_hours_amount" => round($totalhoursamount, 2),
                        "written_off_amount" => round($deletedhoursamount, 2),
                        "manually_written_off_hours_amount" => round($deletedhoursmanuallyamount, 2),
                        "billed_amount" => round($billedhoursamount, 2),
                        "post_billed_credits_amount" => round($postbillamount, 2),
                        "differences" => round($billedhoursamount - $postbillamount, 2)
                    ])
                    ->save();
                unset($matters);
                unset($totalhours);
                unset($deletedhours);
                unset($deletedhoursmanually);
                unset($billedhours);
                unset($totalhoursamount);
                unset($deletedhoursamount);
                unset($deletedhoursmanuallyamount);
                unset($billedhoursamount);
                unset($postbillamount);
            }
        }
    }
    public function clientWrittenOff() {
        if ($this->firm->integration == "practice_panther") {
            return true;
        }else{
            $contacts = CLContact::where("firm_id", $this->firm->id)->where("is_client",1)->get();
            foreach ($contacts as $contact) {
                $matters = $contact->matters;
                foreach($matters as $matter) {
                    //$totalhours =  $matter->timeEntries->where("flat_rate",0)->sum("quantity_in_hours");
                    $totalhours =  0;
                    $deletedhours = 0;
                    $deletedhoursmanually = 0;
                    $billedhours = 0;
                    $totalhoursamount = 0;
                    $deletedhoursamount = 0;
                    $deletedhoursmanuallyamount = 0;
                    $billedhoursamount = 0;
                    $postbillamount = 0;
                    $billedinvoices = $matter->invoicesNotDeleted->whereIn("state",["awaiting_payment","paid"]);
                    $postbillinvoices = $matter->invoicesNotDeleted->where("state","void");
                    $deletedinvoices = $matter->invoicesNotDeleted->where("state","deleted");
                    foreach ($matter->invoicesNotDeleted as $invoice) {
                        $totalhours =  $totalhours + $invoice->invoicelineitem->where("type","ActivityLineItem")->where("kind","Service")->sum("quantity");
                        $totalhoursamount = $totalhoursamount + $invoice->invoicelineitem->where("type","ActivityLineItem")->where("kind","Service")->sum("total");
                        $lineitems =  $invoice->invoicelineitem->where("type","ActivityLineItem")->where("kind","Service");
                        foreach ($lineitems as $item) {
                            $time_Entry = null;
                            $time_Entry = optional($item->timeEntry)->quantity_in_hours;
                            $item_hours = $item->quantity;
                            $price = optional($item->timeEntry)->price;
                            if($time_Entry != null) {
                                $diff = $time_Entry - $item_hours;
                                $deletedhoursmanually =  $deletedhoursmanually +$diff;
                                $amount = $price * $diff;
                                $deletedhoursmanuallyamount = $deletedhoursmanuallyamount + $amount;
                            }

                        }
                    }
                    foreach ($billedinvoices as $invoice) {
                        $billedhours =  $billedhours + $invoice->invoicelineitem->where("type","ActivityLineItem")->where("kind","Service")->sum("quantity");
                        $billedhoursamount = $billedhoursamount + $invoice->invoicelineitem->where("type","ActivityLineItem")->where("kind","Service")->sum("total");
                    }

                    foreach ($deletedinvoices as $invoice1) {
                        $deletedhours =  $deletedhours + $invoice1->invoicelineitem->where("type","ActivityLineItem")->where("kind","Service")->sum("quantity");
                        $deletedhoursamount = $deletedhoursamount + $invoice1->invoicelineitem->where("type","ActivityLineItem")->where("kind","Service")->sum("total");
                    }

                    foreach ($postbillinvoices as $invoice2) {
                        $postbillamount = $postbillamount + $invoice2->invoicelineitem->where("type","ActivityLineItem")->where("kind","Service")->sum("total");
                    }
                    if(strlen($matter->display_number)>180) {
                        $matter_name = substr($matter->display_number,0,180)."...";
                    } else {
                        $matter_name = substr($matter->display_number,0,180);
                    }

                    $this->getRow("\\App\\SummaryWrittenOffByClient",$contact->name,"contact_name")
                        ->fill([
                            "matter_name"=>$matter_name,
                            "total_hours"=>round($totalhours,2),
                            "written_off_hours"=>round($deletedhours,2),
                            "manually_written_off_hours"=>round($deletedhoursmanually,2),
                            "billed_hours"=>round($billedhours,2),
                            "total_hours_amount"=>round($totalhoursamount,2),
                            "written_off_amount"=>round($deletedhoursamount,2),
                            "manually_written_off_hours_amount"=>round($deletedhoursmanuallyamount,2),
                            "billed_amount"=>round($billedhoursamount,2),
                            "post_billed_credits_amount"=>round($postbillamount,2),
                            "differences"=>round($billedhoursamount-$postbillamount,2)])
                        ->save();
                    unset($matters);
                    unset($totalhours);
                    unset($deletedhours);
                    unset($deletedhoursmanually);
                    unset($billedhours);
                    unset($totalhoursamount);
                    unset($deletedhoursamount);
                    unset($deletedhoursmanuallyamount);
                    unset($billedhoursamount);
                    unset($postbillamount);
                }


            }
        }

    }
    public function writeOffs(){
        $this->employeeWrittenOff();
        $this->clientWrittenOff();
    }

    private function getResult($data) {
        $res = ARResource::collection($data);
        return collect(($res)->response()->getData()->data);
    }

}