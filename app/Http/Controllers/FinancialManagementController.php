<?php

namespace App\Http\Controllers;

use App\CLMatter;
use App\Definition;
use App\Http\Libraries\HelperLibrary;
use App\PPMatter;
use App\SummaryAOP;
use App\SummaryMonth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinancialManagementController extends Controller
{
    public function allAOPByRevenue(Request $request) {
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
        }  elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
        } else {
            $financial_year = Definition::getFinancialYear();
        }
        $data_raw = SummaryAOP::where("firm_id", HelperLibrary::getFirmID())
            ->select(["name", "revenue"])->get();
        foreach ($data_raw as $k=>$v) {
            $data[$v->name] = $v->revenue;
        }
        return response()->json([
            "data" => array_values($data),
            "labels" => array_keys($data),
            "state" => $request->scope,
        ]);
    }
    public function allAOPByGPM(Request $request) {
        $data=[];
        $display=0;
        $data_raw = SummaryAOP::where("firm_id", HelperLibrary::getFirmID())
            ->select(["name", "gross_profit_margin"])->get();
        $total = (clone $data_raw)->sum("gross_profit_margin");
        if($total!=0)
        {
            foreach ($data_raw as $k=>$v)
            {
                $data[$v->name] = round(($v->gross_profit_margin / $total) * 100);
            }

        }
        else
        {
            foreach ($data_raw as $k=>$v)
            {
                $data[$v->name] =0;
            }

        }
        if(isset($data["Others"]) and sizeof($data)==1)
        {
            if($data["Others"]>0)
            {
                $display=1;
            }else
            {
                $display=0;
            }
        }
        else
        {
            if(sizeof($data)>0)
            {
                $display=1;
            }else
            {
                $display=0;
            }
        }

        return array("data"=>$data,"display"=>$display);
    }
    public function revenueTrend(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
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
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["revenue"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();

        foreach ($data_raw as $k=>$v) {
            $data[] = $v['revenue']/1000;
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
    public function revenueMom(Request $request)
    {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
        } elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
        } elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
        } else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["revenue_mom"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();

        foreach ($data_raw as $k=>$v) {
            $data[] = $v['revenue_mom'];
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
    public function new_revenue_new(Request $request)
    {
        $revenue = [];
        $revenue_mom = [];
        $count1=0;$count2=0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
        } elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
        } elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
        } else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            $revenue[$i] = SummaryMonth::select('revenue')->where('firm_id',HelperLibrary::getFirmID())
                ->where('month',HelperLibrary::getMonthsFromRange($year,true)[$i])->orderby("month","asc")->get();
            if(isset($revenue[$i][0])) {
                $revenue[$i] = round($revenue[$i][0]->revenue / 1000, 1);
            }else{
                $revenue[$i] = 0 ;
            }
            $revenue_mom[$i] = SummaryMonth::select('revenue_mom')->where('firm_id',HelperLibrary::getFirmID())
                ->where('month',HelperLibrary::getMonthsFromRange($year,true)[$i])->orderby("month","asc")->get();
            if(isset($revenue_mom[$i][0])){
                $revenue_mom[$i] = $revenue_mom[$i][0]->revenue_mom;
            }
            else
            {
                $revenue_mom[$i] = 0;
            }
        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            if($revenue[$i]==0)
            {
                $count1+=1;
            }
            if($revenue_mom[$i]==0)
            {
                $count2+=1;
            }

        }
        if($count1==sizeof($labels) && $count2==sizeof($labels))
        {
            $display=0;
        }
        else
        {
            $display=1;
        }
        return response()->json([
            "revenue" => $revenue,
            "revenue_mom" => $revenue_mom,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
            "display"=>$display
        ]);
    }
    public function expenseTrend(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
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
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["expense"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();

        foreach ($data_raw as $k=>$v) {
            $data[] = $v['expense']/1000;
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
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
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
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["expense_mom"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();

        foreach ($data_raw as $k=>$v) {
            $data[] = $v['expense_mom'];
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
    public function collectionTrend(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailLabels();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
        } else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["collection"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();

        foreach ($data_raw as $k=>$v) {
            $data[] = $v['collection']/1000;
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
    public function new_expense_new(Request $request)
    {
        $expense = [];
        $expense_mom = [];
        $count1=0;$count2=0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
        } elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
        } elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
        } else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            $expense[$i] = SummaryMonth::select('expense')->where('firm_id',HelperLibrary::getFirmID())
                ->where('month',HelperLibrary::getMonthsFromRange($year,true)[$i])->orderby("month","asc")->get();
            if(isset($expense[$i][0])) {
                $expense[$i] = round($expense[$i][0]->expense / 1000, 1);
            }
            else
            {
                $expense[$i] = 0;
            }
            $expense_mom[$i] = SummaryMonth::select('expense_mom')->where('firm_id',HelperLibrary::getFirmID())
                ->where('month',HelperLibrary::getMonthsFromRange($year,true)[$i])->orderby("month","asc")->get();
            if(isset($expense_mom[$i][0])) {
                $expense_mom[$i] = $expense_mom[$i][0]->expense_mom;
            }
            else
            {
                $expense_mom[$i] = 0;
            }

        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            if($expense[$i]==0)
            {
                $count1+=1;
            }
            if($expense_mom[$i]==0)
            {
                $count2+=1;
            }

        }
        if($count1==sizeof($labels) && $count2==sizeof($labels))
        {
            $display=0;
        }
        else
        {
            $display=1;
        }
        return response()->json([
            "expense" => $expense,
            "expense_mom" => $expense_mom,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
            "display"=>$display
        ]);
    }
    public function new_collection_new(Request $request)
    {
        $collection = [];
        $collection_mom = [];
        $count1=0;$count2=0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
        } elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
        } elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
        } else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            $collection[$i] = SummaryMonth::select('collection_rate')->where('firm_id',HelperLibrary::getFirmID())
                ->where('month',HelperLibrary::getMonthsFromRange($year,true)[$i])->orderby("month","asc")->get();
            if(isset($collection[$i][0])){
                $collection[$i] = $collection[$i][0]->collection_rate;
            }
            else{
                $collection[$i] = 0;
            }
            $collection_mom[$i] = SummaryMonth::select('collection_mom')->where('firm_id',HelperLibrary::getFirmID())
                ->where('month',HelperLibrary::getMonthsFromRange($year,true)[$i])->orderby("month","asc")->get();
            if(isset($collection_mom[$i][0])){
                $collection_mom[$i] = $collection_mom[$i][0]->collection_mom;
            }
            else{
                $collection_mom[$i] = 0;
            }
        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            if($collection[$i]==0)
            {
                $count1+=1;
            }
            if($collection_mom[$i]==0)
            {
                $count2+=1;
            }

        }
        if($count1==sizeof($labels) && $count2==sizeof($labels))
        {
            $display=0;
        }
        else
        {
            $display=1;
        }
        return response()->json([
            "collection" => $collection,
            "collection_mom" => $collection_mom,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,"display"=>$display
        ]);
    }
    public function collectionMom(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["collection_mom"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();

        foreach ($data_raw as $k=>$v) {
            $data[] = $v['collection_mom'];
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
    public function grossProfitMarginOverall(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        }  elseif ($request->scope == 'last-12-months') {
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
    public function revenueVsGrossProfitMargin(Request $request)
    {
        $data = [];
        $count=0;
        $display = 0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
        } elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
        } elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
        } else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["overall_gross_profit_margin","revenue"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();
        foreach ($data_raw as $k=>$v) {
            $data['profit'][] = round($v['overall_gross_profit_margin']/1000,1);
            $data['revenue'][] = round($v['revenue']/1000,1);
            if($v['overall_gross_profit_margin'] == 0 and $v['revenue'] ==0)
            {
                $count+=1;
            }
        }


        if($count == sizeof($labels) || $data_raw->isEmpty()) {
            $display = 0;
            $data['profit'][] = 0;
            $data['revenue'][] = 0;
        } else {
            $display = 1;
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
            "display"=>$display
        ]);
    }
}
