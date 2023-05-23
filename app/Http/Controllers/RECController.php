<?php

namespace App\Http\Controllers;

use App\CLInvoice;
use App\CLTimeEntry;
use App\CLUser;
use App\Definition;
use App\Http\Libraries\CollectionLibrary;
use App\Http\Libraries\ExpenseLibrary;
use App\Http\Libraries\HelperLibrary;
use App\Http\Libraries\RevenueLibrary;
use App\PPExpense;
use App\PPInvoice;
use App\PPMatter;
use App\PPTimeEntry;
use App\PPUser;
use App\SummaryMonth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RECController extends Controller
{
    public function revenueData(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        HelperLibrary::logActivity('User Viewed Revenue Page');
        return response()->json([
            "data" => RevenueLibrary::getList(HelperLibrary::getFirmIntegration(), $state, $user, $mt),
            "filters" => [
                "state" => $state,
                "user" => $user,
                "mt" => $mt,
            ]
        ]);
    }
    public function revenueSingleData(Request $request) {
        $month = $request->filled("month") ? $request->month : date("Y-m");
        $data = RevenueLibrary::getSingle(HelperLibrary::getFirmIntegration(), $month);
        HelperLibrary::logActivity('User Viewed Revenue detail Page');
        return response()->json([
            "data" => $data->data,
            "total" => number_format(round($data->total))
        ]);
    }
    public function revenueTrend(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        }
        elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
        }
        else {
            $financial_year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $val = PPInvoice::calcRevenue(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
            } else {
                $val = CLInvoice::calcRevenue(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
            }
            $data[] = round($val / 1000, 0);
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }
    public function revenueMom(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        }
        elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
        }
        else {
            $financial_year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $this_data = PPInvoice::calcRevenue(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
                $last_data = PPInvoice::calcRevenue(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", HelperLibrary::getFirmID(), $user, $mt);
            } else {
                $this_data = CLInvoice::calcRevenue(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
                $last_data = CLInvoice::calcRevenue(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", HelperLibrary::getFirmID(), $user, $mt);
            }
            if ($last_data != 0) {
                $data[] = (($this_data - $last_data) / $last_data) * 100;
            } else {
                $data[] = 0;
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }
    public function revenueByFTE(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("cost_per_hour", "<>", "0")->get();
        foreach ($users as $user) {
            $labels[] = $user->display_name;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPInvoice::calcRevenue($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLInvoice::calcRevenue($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function revenueByAttorney(Request $request) {
        $data=[];
        $revenue_mom=[];
        $mom=[];
        $display=0;
        $count=0;
        $all=[];
        $mom_date="";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $mom_date =date("Y")-2;
            $mom_date =$mom_date."-12";
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $mom_date = Definition::get_MOM_month('-13 months');
        }
        elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
            $mom_date = Definition::get_MOM_month('-10 months');
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $mom_date = Definition::get_MOM_month('-7 months');
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $mom_date = Definition::get_MOM_month('-4 months');
        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
            $mom_date =date("Y")-1;
            $mom_date =$mom_date."-12";
        }
        if(HelperLibrary::getFirmIntegration()=="practice_panther")
        {
            // code here for pp
        }
        else
        {
            if($mt=="all")
            {
//                $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
//                    ->select(["revenue","revenue_mom"])
//                    ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
//                    ->orderby("month", "asc")->get();
//                foreach ($data_raw as $k=>$v) {
//                    $data[] = $v['revenue']/1000;
//                    $revenue_mom[]=$v['revenue_mom'];
//                }
                $all = CLUser::select('id')->where("firm_id",HelperLibrary::getFirmID())->where('type','Attorney')->get();
                for($i=0;$i<sizeof($all);$i++)
                {
                    $all[$i]=$all[$i]->id;
                }
                for($i=0;$i<sizeof($labels);$i++)
                {
                    $data[$i]= CLInvoice::select('total')
                        ->where('firm_id',HelperLibrary::getFirmID())
                        ->whereIn("clio_user_id",$all)
                        ->where('issued_at',"Like",HelperLibrary::getMonthsFromRange($year,true)[$i]."%")
                        ->sum("total");
                    if($data[$i]!=0)
                    {
                        $data[$i] = $data[$i]/1000;
                    }
                }
//                MOM :D :P :)
                $a=sizeof($data)-1;
                for($i=sizeof($data)-1;$i>=0;$i--)
                {
                    if($i==0)
                    {
                        $current=$data[$i];
                        $last= CLInvoice::select('total')->where('firm_id',HelperLibrary::getFirmID())
                            ->whereIn("clio_user_id",$all)->where('issued_at',"Like",$mom_date."%")
                            ->sum("total");
//                            return $last;
                        if($last!=0)
                        {
                            $revenue_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $revenue_mom[$i]=0;
                        }
                    }
                    else
                    {
                        $a=$a-1;
                        $current=$data[$i];
                        $last=$data[$a];
                        if($last!=0)
                        {
                            $revenue_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $revenue_mom[$i]=0;
                        }
                    }
                }
                for($i=0;$i<sizeof($revenue_mom);$i++)
                {
                    $mom[$i]=$revenue_mom[$i];
                }
                for($i=0;$i<sizeof($labels);$i++)
                {
                    if($data[$i]==0 and $mom[$i]==0)
                    {
                        $count+=1;
                    }
                }
                if(sizeof($data)==$count)
                {
                    $display=0;
                }
                else{
                    $display=1;
                }
                return response()->json([
                    "data" => $data,
                    "mom"=>$mom,
                    "labels" => $labels,
                    "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                    "state" => $request->scope,
                    "mt"=>$mt,"display"=>$display
                ]);
            }
            else
            {
//                $attorneyID =  CLUser::select('id')->where('firm_id',HelperLibrary::getFirmID())->where('name',$mt)->get();
                for($i=0;$i<sizeof($labels);$i++)
                {
                    $data[$i]= CLInvoice::select('total')->where('firm_id',HelperLibrary::getFirmID())->where("clio_user_id",$mt)->where('issued_at',"Like",HelperLibrary::getMonthsFromRange($year,true)[$i]."%")
                        ->sum("total");
                    if($data[$i]!=0)
                    {
                        $data[$i] = $data[$i]/1000;
                    }
                }
//           MOM :D :P :)
                $a=sizeof($data)-1;
                for($i=sizeof($data)-1;$i>=0;$i--)
                {
                    if($i==0)
                    {
                        $current=$data[$i];
                        $last= CLInvoice::select('total')->where('firm_id',HelperLibrary::getFirmID())
                            ->where("clio_user_id",$mt)->where('issued_at',"Like",$mom_date."%")
                            ->sum("total");
//                            return $last;
                        if($last!=0)
                        {
                            $revenue_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $revenue_mom[$i]=0;
                        }
                    }
                    else
                    {
                        $a=$a-1;
                        $current=$data[$i];
                        $last=$data[$a];
                        if($last!=0)
                        {
                            $revenue_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $revenue_mom[$i]=0;
                        }
                    }
                }
                for($i=0;$i<sizeof($revenue_mom);$i++)
                {
                    $mom[$i]=$revenue_mom[$i];
                }
                for($i=0;$i<sizeof($labels);$i++)
                {
                    if($data[$i]==0 and $mom[$i]==0)
                    {
                        $count+=1;
                    }
                }
                if(sizeof($data)==$count)
                {
                    $display=0;
                }
                else{
                    $display=1;
                }
                return response()->json([
                    "data" => $data,
                    "mom"=>$mom,
                    "labels" => $labels,
                    "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                    "state" => $request->scope,
                    "mt"=>$mt,"display"=>$display

                ]);
            }

        }

    }
    public function revenueByLegalStaff(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Paralegal Staff")->get();
        foreach ($users as $user) {
            $labels[] = $user->display_name;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPInvoice::calcRevenue($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLInvoice::calcRevenue($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function revenueBySrAssosiate(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Sr. Associate")->get();
        foreach ($users as $user) {
            $labels[] = $user->display_name;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPInvoice::calcRevenue($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLInvoice::calcRevenue($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function revenueByJrAssosiate(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Jr. Associate")->get();
        foreach ($users as $user) {
            $labels[] = $user->display_name;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPInvoice::calcRevenue($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLInvoice::calcRevenue($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function revenueByMatterType(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $mts = PPMatter::where("firm_id", HelperLibrary::getFirmID())->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        foreach ($mts as $matter) {
            $labels[] = $matter->matter_type;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPInvoice::calcRevenue($monthYear, $type, HelperLibrary::getFirmID(), "all", $matter->matter_type);
            } else {
                $data[] = CLInvoice::calcRevenue($monthYear, $type, HelperLibrary::getFirmID(), "all", $matter->matter_type);
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function getRevenueCardData(Request $request) {
        $ytd_before_last = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("revenue")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("before-last"), true))->sum("revenue");
        $ytd_last = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("revenue")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("last"), true))->sum("revenue");
        $ytd = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("revenue")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("this"), true))->sum("revenue");
        $ytd_comparison = $ytd - $ytd_last;
        $ytd_last_comparison = $ytd_last - $ytd_before_last;
        // YTD (avg.)
        $current_fy_days_till_now = (new Carbon(Definition::getFinancialYear()->from))->diff(Carbon::now())->days;
        $ytd_avg_current = ($ytd / ($current_fy_days_till_now / 30));
        // Last FY (avg.)
        $ytd_avg_last = ($ytd_last / 12);
        // Last FY (avg.)
        $ytd_avg_before_last = ($ytd_before_last / 12) ;
        //
        $ytd = number_format(round($ytd / 1000, 0));
        $ytd_last = number_format(round($ytd_last / 1000, 0));
        $ytd_avg_comparison = $ytd_avg_current - $ytd_avg_last;
        $ytd_avg_last_comparison = $ytd_avg_last - $ytd_avg_before_last;
        $key2_label = substr(Definition::getFinancialYearLabels("last")[0], 4, 9);
        if($ytd==0 and $ytd_last==0 and round($ytd_avg_current/1000, 0)==0 and round($ytd_avg_last/1000, 0)==0)
        {
            $display = 0;
        }
        else
        {
            $display = 1;
        }
        return response()->json([
            "key1" => [
                "label" => "YTD",
                "value" => "$".$ytd."K",
                "increase" => $ytd_comparison > 0 ? true : false,
            ],
            "key2" => [
                "label" => "FY-{$key2_label}",
                "value" => "$".$ytd_last."K",
                "increase" => $ytd_last_comparison > 0 ? true : false,
            ],
            "key3" => [
                "label" => "YTD(mo.avg.)",
                "value" => "$".round($ytd_avg_current/1000, 0)."K",
                "increase" => $ytd_avg_comparison > 0 ? true : false,
            ],
            "key4" => [
                "label" => "FY-{$key2_label}(mo.avg.)",
                "value" => "$".round($ytd_avg_last/1000, 0)."K",
                "increase" => $ytd_avg_last_comparison > 0 ? true : false,
            ],
            "display"=>$display
        ]);
    }

    public function revenueDiagnosticsTrend(Request $request) {
        $actual = [];
        $target = [];
        $user = $request->filled("user") ? $request->user : "all";
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if (HelperLibrary::getFirmIntegration()=="practice_panther") {
                $act_val = PPInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
                $tar_val = PPUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            } else {
                $act_val = CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
                $tar_val = CLUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            }
            $actual[] = round(($act_val / 1000), 1);
            $target[] = round(($tar_val / 1000), 1);
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "labels" => $labels,
            "state" => $request->scope,
            "user" => $user,
        ]);
    }
    public function revenueDiagnosticsByAttorneyPartner(Request $request) {
        $actual = [];
        $target = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Owner (Attorney)")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPInvoice::calcRevenueLineItems($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            $actual[] = round(($actualSum / 1000), 1);
            $target[] = round(($targetSum / 1000), 1);
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function revenueDiagnosticsBySrAssociate(Request $request) {
        $actual = [];
        $target = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Sr. Associate")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPInvoice::calcRevenueLineItems($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            $actual[] = round(($actualSum / 1000), 1);
            $target[] = round(($targetSum / 1000), 1);
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function revenueDiagnosticsByJrAssociate(Request $request) {
        $actual = [];
        $target = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Jr. Associate")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPInvoice::calcRevenueLineItems($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            $actual[] = round(($actualSum / 1000), 1);
            $target[] = round(($targetSum / 1000), 1);
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function revenueDiagnosticsByParalegalStaff(Request $request) {
        $actual = [];
        $target = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Paralegal Staff")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPInvoice::calcRevenueLineItems($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            $actual[] = round(($actualSum / 1000), 1);
            $target[] = round(($targetSum / 1000), 1);
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function revenuePredictiveTrend(Request $request) {
        $actual = [];
        $target = [];
        $forecast = [];
        $user = $request->filled("user") ? $request->user : "all";
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        $iterator = 1;
        $m1 = 0;
        $m2 = 0;
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if (HelperLibrary::getFirmIntegration()=="practice_panther") {
                $act_val = PPInvoice::calcRevenueLineItems($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
                $tar_val = PPUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            } else {
                $act_val = CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
                $tar_val = CLUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            }
            if ($iterator >= 1 && $iterator <= 3) {
                $m1 += $act_val;
            }
            if ($iterator >= 2 && $iterator <= 4) {
                $m2 += $act_val;
            }
            $target[] = round(($tar_val / 1000), 1);
            if ($iterator == 5) {
                $actual[] = 0;
                $forecast[] = round((($m1 / 3) / 1000), 1);
            } elseif ($iterator == 6) {
                $actual[] = 0;
                $forecast[] = round((($m2 / 3) / 1000), 1);
            } else {
                $actual[] = round(($act_val / 1000), 1);
                $forecast[] = round(($act_val / 1000),  1);
            }
            $iterator++;
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "forecast" => $forecast,
            "labels" => $labels,
            "state" => $request->scope,
            "user" => $user,
        ]);
    }
    public function revenuePredictiveByAttorneyPartner(Request $request) {
        $actual = [];
        $target = [];
        $forecast = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Owner (Attorney)")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        $iterator = 1;
        $m1 = 0;
        $m2 = 0;
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPInvoice::calcRevenueLineItems($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            if ($iterator >= 1 && $iterator <= 3) {
                $m1 += $actualSum;
            }
            if ($iterator >= 2 && $iterator <= 4) {
                $m2 += $actualSum;
            }
            $target[] = round(($targetSum / 1000), 1);
            if ($iterator == 5) {
                $actual[] = 0;
                $forecast[] = round((($m1 / 3) / 1000), 1);
            } elseif ($iterator == 6) {
                $actual[] = 0;
                $forecast[] = round((($m2 / 3) / 1000), 1);
            } else {
                $actual[] = round(($actualSum / 1000), 1);
                $forecast[] = round(($actualSum / 1000), 1);
            }
            $iterator++;
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "forecast" => $forecast,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function revenuePredictiveBySrAssociate(Request $request) {
        $actual = [];
        $target = [];
        $forecast = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Sr. Associate")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        $iterator = 1;
        $m1 = 0;
        $m2 = 0;
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPInvoice::calcRevenueLineItems($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            if ($iterator >= 1 && $iterator <= 3) {
                $m1 += $actualSum;
            }
            if ($iterator >= 2 && $iterator <= 4) {
                $m2 += $actualSum;
            }
            $target[] = round(($targetSum / 1000), 1);
            if ($iterator == 5) {
                $actual[] = 0;
                $forecast[] = round((($m1 / 3) / 1000), 1);
            } elseif ($iterator == 6) {
                $actual[] = 0;
                $forecast[] = round((($m2 / 3) / 1000), 1);
            } else {
                $actual[] = round(($actualSum / 1000), 1);
                $forecast[] = round(($actualSum / 1000), 1);
            }
            $iterator++;
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "forecast" => $forecast,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function revenuePredictiveByJrAssociate(Request $request) {
        $actual = [];
        $target = [];
        $forecast = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Jr. Associate")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        $iterator = 1;
        $m1 = 0;
        $m2 = 0;
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPInvoice::calcRevenueLineItems($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            if ($iterator >= 1 && $iterator <= 3) {
                $m1 += $actualSum;
            }
            if ($iterator >= 2 && $iterator <= 4) {
                $m2 += $actualSum;
            }
            $target[] = round(($targetSum / 1000), 1);
            if ($iterator == 5) {
                $actual[] = 0;
                $forecast[] = round((($m1 / 3) / 1000), 1);
            } elseif ($iterator == 6) {
                $actual[] = 0;
                $forecast[] = round((($m2 / 3) / 1000), 1);
            } else {
                $actual[] = round(($actualSum / 1000), 1);
                $forecast[] = round(($actualSum / 1000), 1);
            }
            $iterator++;
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "forecast" => $forecast,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function revenuePredictiveByParalegalStaff(Request $request) {
        $actual = [];
        $target = [];
        $forecast = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Paralegal Staff")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        $iterator = 1;
        $m1 = 0;
        $m2 = 0;
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPInvoice::calcRevenueLineItems($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcRevenueTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            if ($iterator >= 1 && $iterator <= 3) {
                $m1 += $actualSum;
            }
            if ($iterator >= 2 && $iterator <= 4) {
                $m2 += $actualSum;
            }
            $target[] = round(($targetSum / 1000), 1);
            if ($iterator == 5) {
                $actual[] = 0;
                $forecast[] = round((($m1 / 3) / 1000), 1);
            } elseif ($iterator == 6) {
                $actual[] = 0;
                $forecast[] = round((($m2 / 3) / 1000), 1);
            } else {
                $actual[] = round(($actualSum / 1000), 1);
                $forecast[] = round(($actualSum / 1000), 1);
            }
            $iterator++;
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "forecast" => $forecast,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function collectionData(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->get("user") : "all";
        HelperLibrary::logActivity('User Viewed Collection Page');
        return response()->json([
            "data" => CollectionLibrary::getList(HelperLibrary::getFirmIntegration(), $state, $user),
            "filters" => [
                "state" => $state,
                "user" => $user,
            ]
        ]);
    }
    public function collectionSingleData(Request $request) {
        $month = $request->filled("month") ? $request->month : date("Y-m");
        $data = CollectionLibrary::getSingle(HelperLibrary::getFirmIntegration(), $month);
        HelperLibrary::logActivity('User Viewed Collection detail Page');
        return response()->json([
            "data" => $data->data,
            "total" => number_format(round($data->total))
        ]);
    }
    public function collectionTrend(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } else {
            $financial_year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPInvoice::calcCollectionSimple(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
            } else {
                $data[] = CLInvoice::calcCollectionSimple(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }
    public function collectionMom(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } else {
            $financial_year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $this_data = PPInvoice::calcCollectionSimple(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
                $last_data = PPInvoice::calcCollectionSimple(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", HelperLibrary::getFirmID(), $user, $mt);
            } else {
                $this_data = CLInvoice::calcCollectionSimple(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
                $last_data = CLInvoice::calcCollectionSimple(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", HelperLibrary::getFirmID(), $user, $mt);
            }
            if ($last_data != 0) {
                $data[] = (($this_data - $last_data) / $last_data) * 100;
            } else {
                $data[] = 0;
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }
    public function collectionByFTE(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("cost_per_hour", "<>", "0")->get();
        foreach ($users as $user) {
            $labels[] = $user->display_name;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPInvoice::calcCollectionSimple($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLInvoice::calcCollectionSimple($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function collectionByAttorney(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Owner (Attorney)")->get();
        foreach ($users as $user) {
            $labels[] = $user->display_name;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPInvoice::calcCollectionSimple($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLInvoice::calcCollectionSimple($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        $count=0;
        for($i=0;$i<sizeof($labels);$i++)
        {
            if($data[$i]==0)
            {
                $count++;
            }
        }
        if($count==sizeof($labels))
        {
            $display=0;
        }
        else
        {
            $display=1;
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),"display"=>$display
        ]);
    }
    public function collectionByLegalStaff(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Paralegal Staff")->get();
        foreach ($users as $user) {
            $labels[] = $user->display_name;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPInvoice::calcCollectionSimple($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLInvoice::calcCollectionSimple($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function collectionByMatterType(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $mts = PPMatter::where("firm_id", HelperLibrary::getFirmID())->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        foreach ($mts as $matter) {
            $labels[] = $matter->matter_type;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPInvoice::calcCollectionSimple($monthYear, $type, HelperLibrary::getFirmID(), "all", $matter->matter_type);
            } else {
                $data[] = CLInvoice::calcCollectionSimple($monthYear, $type, HelperLibrary::getFirmID(), "all", $matter->matter_type);
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function getCollectionsCardData(Request $request) {
        $ytd_before_last = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("collection")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("before-last"), true))->sum("collection");
        $ytd_last = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("collection")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("last"), true))->sum("collection");
        $ytd = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("collection")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("this"), true))->sum("collection");
        $ytd_comparison = $ytd - $ytd_last;
        $ytd_last_comparison = $ytd_last - $ytd_before_last;
        // YTD (avg.)
        $current_fy_days_till_now = (new Carbon(Definition::getFinancialYear()->from))->diff(Carbon::now())->days;
        $ytd_avg_current = ($ytd / ($current_fy_days_till_now / 30));
        // Last FY (avg.)
        $ytd_avg_last = ($ytd_last / 12);
        // Last FY (avg.)
        $ytd_avg_before_last = ($ytd_before_last / 12);
        //
        $ytd = number_format(round($ytd / 1000, 0));
        $ytd_last = number_format(round($ytd_last / 1000, 0));
        $ytd_avg_comparison = $ytd_avg_current - $ytd_avg_last;
        $ytd_avg_last_comparison = $ytd_avg_last - $ytd_avg_before_last;
        $key2_label = substr(Definition::getFinancialYearLabels("last")[0], 4, 9);
        if($ytd==0 and $ytd_last==0 and round($ytd_avg_current/1000, 0)==0 and round($ytd_avg_last/1000, 0)==0)
        {
            $display = 0;
        }
        else
        {
            $display = 1;
        }

        return response()->json([
            "key1" => [
                "label" => "YTD",
                "value" => "$".$ytd."K",
                "increase" => $ytd_comparison > 0 ? true : false,
            ],
            "key2" => [
                "label" => "FY-{$key2_label}",
                "value" => "$".$ytd_last."K",
                "increase" => $ytd_last_comparison > 0 ? true : false,
            ],
            "key3" => [
                "label" => "YTD(mo.avg.)",
                "value" => "$".round($ytd_avg_current/1000, 0)."K",
                "increase" => $ytd_avg_comparison > 0 ? true : false,
            ],
            "key4" => [
                "label" => "FY-{$key2_label}(mo.avg.)",
                "value" => "$".round($ytd_avg_last/1000, 0)."K",
                "increase" => $ytd_avg_last_comparison > 0 ? true : false,
            ],
            "display"=>$display
        ]);
    }
    public function expenseData(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        HelperLibrary::logActivity('User Viewed Expense Page');
        return response()->json([
            "data" => ExpenseLibrary::getList(HelperLibrary::getFirmIntegration(), $state, $mt),
            "filters" => [
                "state" => $state,
                "mt" => $mt,
            ]
        ]);
    }
    public function expenseSingleData(Request $request) {
        $month = $request->filled("month") ? $request->month : date("Y-m");
        $data = ExpenseLibrary::getSingle(HelperLibrary::getFirmIntegration(), $month);
        HelperLibrary::logActivity('User Viewed Expense detail Page');
        return response()->json([
            "data" => $data->data,
            "total" => number_format(round($data->total))
        ]);
    }
    public function expenseByFTE(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("cost_per_hour", "<>", "0")->get();
        foreach ($users as $user) {
            $labels[] = $user->display_name;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPExpense::calcExpense($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLTimeEntry::calcExpense($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function expenseByAttorney(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Owner (Attorney)")->get();
        foreach ($users as $user) {
            $labels[] = $user->display_name;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPExpense::calcExpense($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLTimeEntry::calcExpense($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function expenseByLegalStaff(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("type", "Paralegal Staff")->get();
        foreach ($users as $user) {
            $labels[] = $user->display_name;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPExpense::calcExpense($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLTimeEntry::calcExpense($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function expenseByMatterType(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $mts = PPMatter::where("firm_id", HelperLibrary::getFirmID())->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        foreach ($mts as $matter) {
            $labels[] = $matter->matter_type;
            $colors[] = $this->getRandomColor();
            if ($request->scope == 'today') {
                $monthYear = date("Y-m-d");
                $type = "today";
            } elseif ($request->scope == 'this-month') {
                $monthYear = date("Y-m");
                $type = "month";
            } elseif ($request->scope == 'last-month') {
                $monthYear = date("Y-m", strtotime("-1 month"));
                $type = "month";
            } elseif ($request->scope == 'this-year') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            } elseif ($request->scope == 'last-year') {
                $monthYear = Definition::getFinancialYear("last");
                $type = "year";
            } elseif ($request->scope == 'last-12-months') {
                $monthYear = Definition::getYearTrail();
                $type = "year";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[] = PPExpense::calcExpense($monthYear, $type, HelperLibrary::getFirmID(),"all", $matter->matter_type);
            } else {
                $data[] = CLTimeEntry::calcExpense($monthYear, $type, HelperLibrary::getFirmID(), "all", $matter->matter_type);
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "state" => $request->scope,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
        ]);
    }
    public function getExpensesCardData(Request $request) {
        $ytd_before_last = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("expense")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("before-last"), true))->sum("expense");
        $ytd_last = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("expense")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("last"), true))->sum("expense");
        $ytd = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("expense")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("this"), true))->sum("expense");
        $ytd_comparison = $ytd - $ytd_last;
        $ytd_last_comparison = $ytd_last - $ytd_before_last;
        // YTD (avg.)
        $current_fy_days_till_now = (new Carbon(Definition::getFinancialYear()->from))->diff(Carbon::now())->days;
        $ytd_avg_current = ($ytd / ($current_fy_days_till_now / 30));
        // Last FY (avg.)
        $ytd_avg_last = ($ytd_last / 12) ;
        // Last FY (avg.)
        $ytd_avg_before_last = ($ytd_before_last / 12) ;
        //
        $ytd = number_format(round($ytd / 1000, 0));
        $ytd_last = number_format(round($ytd_last / 1000, 0));
        $ytd_avg_comparison = $ytd_avg_current - $ytd_avg_last;
        $ytd_avg_last_comparison = $ytd_avg_last - $ytd_avg_before_last;
        $key2_label = substr(Definition::getFinancialYearLabels("last")[0], 4, 9);
        if($ytd==0 and $ytd_last==0 and round($ytd_avg_current/1000, 0)==0 and round($ytd_avg_last/1000, 0)==0)
        {
            $display = 0;
        }
        else
        {
            $display = 1;
        }

        return response()->json([
            "key1" => [
                "label" => "YTD",
                "value" => "$".$ytd."K",
                "increase" => $ytd_comparison > 0 ? true : false,
            ],
            "key2" => [
                "label" => "FY-{$key2_label}",
                "value" => "$".$ytd_last."K",
                "increase" => $ytd_last_comparison > 0 ? true : false,
            ],
            "key3" => [
                "label" => "YTD(mo.avg.)",
                "value" => "$".round($ytd_avg_current/1000, 0)."K",
                "increase" => $ytd_avg_comparison > 0 ? true : false,
            ],
            "key4" => [
                "label" => "FY-{$key2_label}(mo.avg.)",
                "value" => "$".round($ytd_avg_last/1000, 0)."K",
                "increase" => $ytd_avg_last_comparison > 0 ? true : false,
            ],
            "display"=>$display
        ]);
    }
    public function expenseTrend(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        }
        elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
        }
        else {
            $financial_year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $val = PPExpense::calcExpense(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
            } else {
                $val = CLTimeEntry::calcExpense(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
            }
            $data[] = round($val / 1000, 0);
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }
    public function expenseMom(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        }
        elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
        }
        else {
            $financial_year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $this_data = PPExpense::calcExpense(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
                $last_data = PPExpense::calcExpense(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", HelperLibrary::getFirmID(), $user, $mt);
            } else {
                $this_data = CLTimeEntry::calcExpense(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
                $last_data = CLTimeEntry::calcExpense(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", HelperLibrary::getFirmID(), $user, $mt);
            }
            if ($last_data != 0) {
                $data[] = (($this_data - $last_data) / $last_data) * 100;
            } else {
                $data[] = 0;
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }
    public function expenseDiagnosticsTrend(Request $request) {
        $actual = [];
        $target = [];
        $user = $request->filled("user") ? $request->user : "all";
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if (HelperLibrary::getFirmIntegration()=="practice_panther") {
                $act_val = PPExpense::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
                $tar_val = PPUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            } else {
                $act_val = CLTimeEntry::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
                $tar_val = CLUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            }
            $actual[] = round(($act_val / 1000), 1);
            $target[] = round(($tar_val / 1000), 1);
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "labels" => $labels,
            "state" => $request->scope,
            "user" => $user,
        ]);
    }
    public function expenseDiagnosticsByAttorneyPartner(Request $request) {
        $actual = [];
        $target = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated", true)->where("type", "Owner (Attorney)")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPExpense::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            $actual[] = round(($actualSum / 1000), 1);
            $target[] = round(($targetSum / 1000), 1);
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function expenseDiagnosticsBySrAssociate(Request $request) {
        $actual = [];
        $target = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated", true)->where("type", "Sr. Associate")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPExpense::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            $actual[] = round(($actualSum / 1000), 1);
            $target[] = round(($targetSum / 1000), 1);
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function expenseDiagnosticsByJrAssociate(Request $request) {
        $actual = [];
        $target = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated", true)->where("type", "Jr. Associate")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPExpense::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            $actual[] = round(($actualSum / 1000), 1);
            $target[] = round(($targetSum / 1000), 1);
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function expenseDiagnosticsByParalegalStaff(Request $request) {
        $actual = [];
        $target = [];
        $financial_year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated", true)->where("type", "Paralegal Staff")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPExpense::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            $actual[] = round(($actualSum / 1000), 1);
            $target[] = round(($targetSum / 1000), 1);
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function expensePredictiveTrend(Request $request) {
        $actual = [];
        $target = [];
        $forecast = [];
        $user = $request->filled("user") ? $request->user : "all";
        $financial_year = Definition::getHalfYearTrailPredictive();
        $labels = Definition::getHalfYearTrailPredictiveLabels();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        $iterator = 1;
        $m1 = 0;
        $m2 = 0;
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if (HelperLibrary::getFirmIntegration()=="practice_panther") {
                $act_val = PPExpense::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
                $tar_val = PPUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            } else {
                $act_val = CLTimeEntry::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
                $tar_val = CLUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            }
            if ($iterator >= 1 && $iterator <= 3) {
                $m1 += $act_val;
            }
            if ($iterator >= 2 && $iterator <= 4) {
                $m2 += $act_val;
            }
            $target[] = round(($tar_val / 1000), 1);
            if ($iterator == 5) {
                $actual[] = 0;
                $forecast[] = round((($m1 / 3) / 1000), 1);
            } elseif ($iterator == 6) {
                $actual[] = 0;
                $forecast[] = round((($m2 / 3) / 1000), 1);
            } else {
                $actual[] = round(($act_val / 1000), 1);
                $forecast[] = round(($act_val / 1000), 1);
            }
            $iterator++;
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "forecast" => $forecast,
            "labels" => $labels,
            "state" => $request->scope,
            "user" => $user,
        ]);
    }
    public function expensePredictiveByAttorneyPartner(Request $request) {
        $actual = [];
        $target = [];
        $forecast = [];
        $financial_year = Definition::getHalfYearTrailPredictive();
        $labels = Definition::getHalfYearTrailPredictiveLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated", true)->where("type", "Owner (Attorney)")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        $iterator = 1;
        $m1 = 0;
        $m2 = 0;
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPExpense::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            if ($iterator >= 1 && $iterator <= 3) {
                $m1 += $actualSum;
            }
            if ($iterator >= 2 && $iterator <= 4) {
                $m2 += $actualSum;
            }
            $target[] = round(($targetSum / 1000), 1);
            if ($iterator == 5) {
                $actual[] = 0;
                $forecast[] = round((($m1 / 3) / 1000), 1);
            } elseif ($iterator == 6) {
                $actual[] = 0;
                $forecast[] = round((($m2 / 3) / 1000), 1);
            } else {
                $actual[] = round(($actualSum / 1000), 1);
                $forecast[] = round(($actualSum / 1000), 1);
            }
            $iterator++;
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "forecast" => $forecast,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function expensePredictiveBySrAssociate(Request $request) {
        $actual = [];
        $target = [];
        $forecast = [];
        $financial_year = Definition::getHalfYearTrailPredictive();
        $labels = Definition::getHalfYearTrailPredictiveLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated", true)->where("type", "Sr. Associate")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        $iterator = 1;
        $m1 = 0;
        $m2 = 0;
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPExpense::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            if ($iterator >= 1 && $iterator <= 3) {
                $m1 += $actualSum;
            }
            if ($iterator >= 2 && $iterator <= 4) {
                $m2 += $actualSum;
            }
            $target[] = round(($targetSum / 1000), 1);
            if ($iterator == 5) {
                $actual[] = 0;
                $forecast[] = round((($m1 / 3) / 1000), 1);
            } elseif ($iterator == 6) {
                $actual[] = 0;
                $forecast[] = round((($m2 / 3) / 1000), 1);
            } else {
                $actual[] = round(($actualSum / 1000), 1);
                $forecast[] = round(($actualSum / 1000), 1);
            }
            $iterator++;
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "forecast" => $forecast,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function expensePredictiveByJrAssociate(Request $request) {
        $actual = [];
        $target = [];
        $forecast = [];
        $financial_year = Definition::getHalfYearTrailPredictive();
        $labels = Definition::getHalfYearTrailPredictiveLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated", true)->where("type", "Jr. Associate")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        $iterator = 1;
        $m1 = 0;
        $m2 = 0;
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPExpense::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            if ($iterator >= 1 && $iterator <= 3) {
                $m1 += $actualSum;
            }
            if ($iterator >= 2 && $iterator <= 4) {
                $m2 += $actualSum;
            }
            $target[] = round(($targetSum / 1000), 1);
            if ($iterator == 5) {
                $actual[] = 0;
                $forecast[] = round((($m1 / 3) / 1000), 1);
            } elseif ($iterator == 6) {
                $actual[] = 0;
                $forecast[] = round((($m2 / 3) / 1000), 1);
            } else {
                $actual[] = round(($actualSum / 1000), 1);
                $forecast[] = round(($actualSum / 1000), 1);
            }
            $iterator++;
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "forecast" => $forecast,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function expensePredictiveByParalegalStaff(Request $request) {
        $actual = [];
        $target = [];
        $forecast = [];
        $financial_year = Definition::getHalfYearTrailPredictive();
        $labels = Definition::getHalfYearTrailPredictiveLabels();
        $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated", true)->where("type", "Paralegal Staff")->get();
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        $iterator = 1;
        $m1 = 0;
        $m2 = 0;
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $actualSum = 0;
            $targetSum = 0;
            foreach ($users as $usr) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $actualSum += PPExpense::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += PPUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                } else {
                    $actualSum += CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                    $targetSum += CLUser::calcExpenseTarget($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $usr->id);
                }
            }
            if ($iterator >= 1 && $iterator <= 3) {
                $m1 += $actualSum;
            }
            if ($iterator >= 2 && $iterator <= 4) {
                $m2 += $actualSum;
            }
            $target[] = round(($targetSum / 1000), 1);
            if ($iterator == 5) {
                $actual[] = 0;
                $forecast[] = round((($m1 / 3) / 1000), 1);
            } elseif ($iterator == 6) {
                $actual[] = 0;
                $forecast[] = round((($m2 / 3) / 1000), 1);
            } else {
                $actual[] = round(($actualSum / 1000), 1);
                $forecast[] = round(($actualSum / 1000), 1);
            }
            $iterator++;
        }
        return response()->json([
            "actual" => $actual,
            "target" => $target,
            "forecast" => $forecast,
            "labels" => $labels,
            "state" => $request->scope,
        ]);
    }
    public function grossProfitMarginOverall(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["overall_gross_profit_margin"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();

        foreach ($data_raw as $k=>$v) {
            $data[] = $v['overall_gross_profit_margin'];
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }
    public function getBillablesCardData(Request $request) {
        $ytd_before_last = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("billable_hours")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("before-last"), true))->sum("billable_hours");
        $ytd_last = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("billable_hours")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("last"), true))->sum("billable_hours");
        $ytd = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select("billable_hours")
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("this"), true))->sum("billable_hours");
        $ytd_comparison = $ytd - $ytd_last;
        $ytd_last_comparison = $ytd_last - $ytd_before_last;
        // YTD (avg.)
        $current_fy_days_till_now = (new Carbon(Definition::getFinancialYear()->from))->diff(Carbon::now())->days;
        $ytd_avg_current = ($ytd / ($current_fy_days_till_now / 30));
        // Last FY (avg.)
        $ytd_avg_last = ($ytd_last / 12) ;
        // Last FY (avg.)
        $ytd_avg_before_last = ($ytd_before_last / 12);
        //
        $ytd = number_format(round($ytd, 0));
        $ytd_last = number_format(round($ytd_last, 0));
        $ytd_avg_comparison = $ytd_avg_current - $ytd_avg_last;
        $ytd_avg_last_comparison = $ytd_avg_last - $ytd_avg_before_last;
        $key2_label = substr(Definition::getFinancialYearLabels("last")[0], 4, 9);
        if($ytd==0 and $ytd_last==0 and round($ytd_avg_current/1000, 0)==0 and round($ytd_avg_last/1000, 0)==0)
        {
            $display = 0;
        }
        else
        {
            $display = 1;
        }

        return response()->json([
            "key1" => [
                "label" => "YTD",
                "value" => $ytd." <small>Hrs.</small>",
                "increase" => $ytd_comparison > 0 ? true : false,
            ],
            "key2" => [
                "label" => "FY-{$key2_label}",
                "value" => $ytd_last." <small>Hrs.</small>",
                "increase" => $ytd_last_comparison > 0 ? true : false,
            ],
            "key3" => [
                "label" => "YTD(mo.avg.)",
                "value" => round($ytd_avg_current, 0)." <small>Hrs.</small>",
                "increase" => $ytd_avg_comparison > 0 ? true : false,
            ],
            "key4" => [
                "label" => "FY-{$key2_label}(mo.avg.)",
                "value" => round($ytd_avg_last, 0)." <small>Hrs.</small>",
                "increase" => $ytd_avg_last_comparison > 0 ? true : false,
            ],
            "display"=>$display
        ]);
    }
    public function getCreditRefundCardData(Request $request) {
        $count1=0;$count2=0;$display=0;
        $year = Definition::getHalfYearTrail();
        $labels = Definition::getHalfYearTrailLabels();
        $data = ["credits"=>[], "refunds"=>[]];
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select(["credits", "refunds"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))->get();
        foreach ($data_raw as $v) {
            $data["credits"][] = round($v['credits']/1000,1);
            $data["refunds"][] = round($v['refunds']/1000,1);
        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            if(isset($data['credits'][$i]) and $data['credits'][$i]==0)
            {
                $count1+=1;
            }
            if(isset($data['refunds'][$i]) and $data['refunds'][$i]==0)
            {
                $count2+=1;
            }
        }
        if($count1==sizeof($labels) and $count2==sizeof($labels))
        {
            $display = 0;
        }
        elseif($count1 == 0 and $count2 == 0)
        {
            $display = 0;
        }
        else
        {
            $display = 1;
        }

        return response()->json([

            "data" => $data,
            "labels" => $labels,"display"=>$display
        ]);
    }
}
