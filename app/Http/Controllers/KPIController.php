<?php

namespace App\Http\Controllers;

use App\CLContact;
use App\CLInvoice;
use App\CLMatter;
use App\CLTimeEntry;
use App\CLUser;
use App\Definition;
use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\SummaryWrittenOffByEmployeeResource;
use App\PPInvoice;
use App\PPMatter;
use App\PPTimeEntry;
use App\PPUser;
use App\SummaryMatter;
use App\SummaryMonth;
use App\SummaryUser;
use App\SummaryWrittenOffByEmployee;
use Illuminate\Http\Request;
use mysql_xdevapi\Collection;

class KPIController extends Controller
{
    public function utilizationTrend(Request $request) {
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
            ->select(["utilization_rate"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();

        foreach ($data_raw as $k=>$v) {
            $data[] = $v['utilization_rate'];
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
    //new work
    public function productivityWorkedTime(Request $request) {
        $worked_time = [];
        $worked_time_mom=[];
        $count1=0;$count2=0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

        }
           elseif ($request->scope == 'last-9-months') {
             $year = Definition::getNineMonthsTrail();
             $labels = Definition::getNineMonthsTrailLabels();
               $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

           }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

        }
        if($user == "all")
        {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["worked_time","worked_time_mom",])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
          if($year!='last-9-months' && $year!='last-6-months' && $year!='last-3-months') {
              for ($i = 0; $i < sizeof($months); $i++) {
//               echo $months[$i]->month;
                  $worked_time[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                      ->select(["worked_time"])
                      ->where("month", $months[$i]->month)
                      ->orderby("month", "asc")->sum('worked_time');
                  $worked_time_mom[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                      ->select(["worked_time_mom"])
                      ->where("month", $months[$i]->month)
                      ->orderby("month", "asc")->sum('worked_time_mom');
              }
          }

       }
        else
        {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["worked_time","worked_time_mom",])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
        }
        if($user!='all')
        {
            foreach ($data_raw as $k => $v)
            {
                $worked_time[] = $v['worked_time'];
                $worked_time_mom[] = $v['worked_time_mom'];
            }
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($worked_time[$i]==0)
                {
                    $count1+=1;
                }
                if($worked_time_mom[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display = 0;
            }
            else
            {
                $display = 1;
            }

            return response()->json([
                "worked_time" => $worked_time,
                "worked_time_mom" => $worked_time_mom,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
        }
        else
        {
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($worked_time[$i]==0)
                {
                    $count1+=1;
                }
                if($worked_time_mom[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display = 0;
            }
            else
            {
                $display = 1;
            }
            return response()->json([
                "worked_time" => $worked_time,
                "worked_time_mom" => $worked_time_mom,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
//            return $months[1]->month;
        }
    }
    //worked MOM
    public function productivityWorkedMOM(Request $request) {
        $data = [];
        $months=[];
        $count1=0;
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
        $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        if($user == "all"){
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["worked_time_mom"])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            for($i=0;$i<12;$i++)
            {
//              echo $months[$i]->month;
                $data[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["worked_time_mom"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('worked_time_mom');
            }
            foreach ($data_raw as $k=>$v) {
                $data[] = $v['worked_time_mom'];
            }
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($data[$i]==0)
                {
                    $count1+=1;
                }
            }
            if($count1==sizeof($labels))
            {
                $display = 0;
            }
            else
            {
                $display = 1;
            }
            return response()->json([
                "data" => $data,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
        } else {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["worked_time_mom"])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            foreach ($data_raw as $k=>$v) {
                $data[] = $v['worked_time_mom'];
            }
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($data[$i]==0)
                {
                    $count1+=1;
                }
            }
            if($count1==sizeof($labels))
            {
                $display = 0;
            }
            else
            {
                $display = 1;
            }
            return response()->json([
                "data" => $data,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
        }


    }
    public function getUserWorkTime(Request $request)
    {
        $worked_time = [];
        $monthly_billable_target = [];
        $count1=0;$count2=0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

        }
        elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();

        }
        if($user == "all")
        {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["worked_time","monthly_billable_target"])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
                for ($i = 0; $i < sizeof($months); $i++) {
//               echo $months[$i]->month;
                    $worked_time[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                        ->select(["worked_time"])
                        ->where("month", $months[$i]->month)
                        ->orderby("month", "asc")->sum('worked_time');
                    $monthly_billable_target[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                        ->select(["monthly_billable_target"])
                        ->where("month", $months[$i]->month)
                        ->orderby("month", "asc")->sum('monthly_billable_target');
                }
            }


        else
        {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["worked_time","monthly_billable_target"])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
        }
        if($user!='all')
        {
            foreach ($data_raw as $k => $v)
            {
                $worked_time[] = $v['worked_time'];
                $monthly_billable_target[] = $v['monthly_billable_target'];
            }
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($worked_time[$i]==0)
                {
                    $count1+=1;
                }
                if($monthly_billable_target[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "worked_time" => $worked_time,
                "monthly_billable_target" => $monthly_billable_target,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
        }
        else
        {
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($worked_time[$i]==0)
                {
                    $count1+=1;
                }
                if($monthly_billable_target[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "worked_time" => $worked_time,
                "monthly_billable_target" => $monthly_billable_target,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
//            return $months[1]->month;
        }

    }
// productivity Available

    public function productivityAvailableTime(Request $request) {
        $available_time = [];
        $count1=0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        if($user == "all"){
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["available_time"])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            for($i=0;$i<sizeof($months);$i++)
            {
                $available_time[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["available_time"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('available_time');
            }

        } else {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["available_time",])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
        }
        if($user!='all')
        {
            foreach ($data_raw as $k => $v)
            {
                $available_time[] = $v['available_time'];
            }
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($available_time[$i]==0)
                {
                    $count1+=1;
                }
            }
            if($count1==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "data" => $available_time,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
        }
        else
        {
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($available_time[$i]==0)
                {
                    $count1+=1;
                }
            }
            if($count1==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "data" => $available_time,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);

        }
    }
    //availableMoM

    public function productivityAvailableMOM(Request $request) {
        $data = [];
        $months=[];
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
        $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        if($user == "all"){
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["available_time_mom"])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            for($i=0;$i<12;$i++)
            {
//              echo $months[$i]->month;
                $data[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["available_time_mom"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('available_time_mom');
            }
            foreach ($data_raw as $k=>$v) {
                $data[] = $v['available_time_mom'];
            }
            return response()->json([
                "data" => $data,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,
            ]);

        } else {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["available_time_mom"])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            foreach ($data_raw as $k=>$v) {
                $data[] = $v['available_time_mom'];
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


    }
    // Productivity Billed Time

    public function productivityBilledTime(Request $request) {
        $billed_time = [];
        $billed_hours_mom=[];
        $count1=0;$count2=0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        if($user == "all"){
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["billed_time","billed_hours_mom",])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            for($i=0;$i<sizeof($months);$i++)
            {
//               echo $months[$i]->month;
                $billed_time[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["billed_time"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('billed_time');
                $billed_hours_mom[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["billed_hours_mom"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('billed_hours_mom');
            }

        } else {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["billed_time","billed_hours_mom",])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
        }
        if($user!='all')
        {
            foreach ($data_raw as $k => $v)
            {
                $billed_time[] = $v['billed_time'];
                $billed_hours_mom[] = $v['billed_hours_mom'];
            }
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($billed_time[$i]==0)
                {
                    $count1+=1;
                }
                if($billed_hours_mom[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "billed_time" => $billed_time,
                "billed_hours_mom" => $billed_hours_mom,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
        }
        else
        {
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($billed_time[$i]==0)
                {
                    $count1+=1;
                }
                if($billed_hours_mom[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "billed_time" => $billed_time,
                "billed_hours_mom" => $billed_hours_mom,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);

        }
    }
    //billed-MOM
    public function productivityBilledMOM(Request $request) {
        $data = [];
        $months=[];
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
        $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        if($user == "all"){
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["billed_hours_mom"])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            for($i=0;$i<12;$i++)
            {
//              echo $months[$i]->month;
                $data[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["billed_hours_mom"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('billed_hours_mom');
            }
            foreach ($data_raw as $k=>$v) {
                $data[] = $v['billed_hours_mom'];
            }
            return response()->json([
                "data" => $data,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,
            ]);

        } else {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["billed_hours_mom"])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            foreach ($data_raw as $k=>$v) {
                $data[] = $v['billed_hours_mom'];
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

    }
// productivity collected Time
    public function productivityCollectedTime(Request $request) {
        $collected_time = [];
        $collected_time_mom=[];
        $count1=0;$count2=0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        if($user == "all"){
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["collected_time","collected_time_mom",])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            for($i=0;$i<sizeof($months);$i++)
            {
//               echo $months[$i]->month;
                $collected_time[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["collected_time"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('collected_time');
                $collected_time_mom[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["collected_time_mom"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('collected_time_mom');
            }

        } else {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["collected_time","collected_time_mom",])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
        }
        if($user!='all')
        {
            foreach ($data_raw as $k => $v)
            {
                $collected_time[] = $v['collected_time'];
                $collected_time_mom[] = $v['collected_time_mom'];
            }
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($collected_time[$i]==0)
                {
                    $count1+=1;
                }
                if($collected_time_mom[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "collected_time" => $collected_time,
                "collected_time_mom" => $collected_time_mom,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
        }
        else
        {
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($collected_time[$i]==0)
                {
                    $count1+=1;
                }
                if($collected_time_mom[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "collected_time" => $collected_time,
                "collected_time_mom" => $collected_time_mom,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
//            return $months[1]->month;
        }
    }
    //collected MoM
    public function productivityCollectedMOM(Request $request) {
        $data = [];
        $months=[];
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
        $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        if($user == "all"){
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["collected_time_mom"])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            for($i=0;$i<12;$i++)
            {
//              echo $months[$i]->month;
                $data[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["collected_time_mom"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('collected_time_mom');
            }
            foreach ($data_raw as $k=>$v) {
                $data[] = $v['collected_time_mom'];
            }
            return response()->json([
                "data" => $data,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,
            ]);

        } else {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["collected_time_mom"])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            foreach ($data_raw as $k=>$v) {
                $data[] = $v['collected_time_mom'];
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

    }


// project Management Billables

    public function getBillables_perUser(Request $request) {
        $billed_hours_mom = [];
        $billable_hours=[];
        $count1=0;$count2=0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        if($user == "all"){
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["billable_hours","billed_hours_mom",])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            for($i=0;$i<sizeof($months);$i++)
            {
//               echo $months[$i]->month;
                $billed_hours_mom[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["billed_hours_mom"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('billed_hours_mom');
                $billable_hours[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["billable_hours"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('billable_hours');
            }

        } else {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["billable_hours","billed_hours_mom",])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
        }
        if($user!='all')
        {
            foreach ($data_raw as $k => $v)
            {
                $billed_hours_mom[] = $v['billed_hours_mom'];
                $billable_hours[] = $v['billable_hours'];
            }
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($billed_hours_mom[$i]==0)
                {
                    $count1+=1;
                }
                if($billable_hours[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "billed_hours_mom" => $billed_hours_mom,
                "billable_hours" => $billable_hours,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
        }
        else
        {
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($billed_hours_mom[$i]==0)
                {
                    $count1+=1;
                }
                if($billable_hours[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "billed_hours_mom" => $billed_hours_mom,
                "billable_hours" => $billable_hours,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
//            return $months[1]->month;
        }

    }

    //billable MOM
   /* public function BillablePerUserMom(Request $request) {
        $non_billed_hours=[];
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
        if($user=="all")
        {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["non_billed_hours","non_billed_hours_mom",])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
        }
        else{
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["non_billed_hours","non_billed_hours_mom",])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
        }


        foreach ($data_raw as $k=>$v) {
            $non_billed_hours_mom[] = $v['non_billed_hours_mom'];
            $non_billed_hours[] = $v['non_billed_hours'];
        }
        return response()->json([
            "non_billable_hours" => $non_billed_hours,
            "non_billed_hours_mom" => $non_billed_hours_mom,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);

    }*/

    public function getNonBillables_perUser(Request $request) {
        $non_billed_hours_mom = [];
        $non_billed_hours=[];
        $count1=0;$count2=0;
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
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        if($user == "all"){
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["non_billed_hours_mom","non_billed_hours",])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            for($i=0;$i<sizeof($months);$i++)
            {
//               echo $months[$i]->month;
                $non_billed_hours_mom[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["non_billed_hours_mom"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('non_billed_hours_mom');
                $non_billed_hours[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["non_billed_hours"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('non_billed_hours');
            }

        } else {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["non_billed_hours_mom","non_billed_hours",])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
        }
        if($user!='all')
        {
            foreach ($data_raw as $k => $v)
            {
                $non_billed_hours_mom[] = $v['non_billed_hours_mom'];
                $non_billed_hours[] = $v['non_billed_hours'];
            }
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($non_billed_hours[$i]==0)
                {
                    $count1+=1;
                }
                if($non_billed_hours_mom[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "non_billed_hours_mom" => $non_billed_hours_mom,
                "non_billed_hours" => $non_billed_hours,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
        }
        else
        {
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($non_billed_hours[$i]==0)
                {
                    $count1+=1;
                }
                if($non_billed_hours_mom[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "non_billed_hours_mom" => $non_billed_hours_mom,
                "non_billed_hours" => $non_billed_hours,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
//            return $months[1]->month;
        }

    }
    public function getBillablesVsNonBillables_perUser(Request $request) {
        $billable_hours = [];
        $non_billed_hours=[];
        $count1=0;$count2=0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        if($user == "all"){
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["billable_hours","non_billed_hours",])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
            for($i=0;$i<sizeof($months);$i++)
            {
//               echo $months[$i]->month;
                $billable_hours[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["billable_hours"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('billable_hours');
                $non_billed_hours[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                    ->select(["non_billed_hours"])
                    ->where("month", $months[$i]->month)
                    ->orderby("month", "asc")->sum('non_billed_hours');
            }

        } else {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["billable_hours","non_billed_hours",])->where('user',$user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
        }
        if($user!='all')
        {
            foreach ($data_raw as $k => $v)
            {
                $billable_hours[] = $v['billable_hours'];
                $non_billed_hours[] = $v['non_billed_hours'];
            }
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($billable_hours[$i]==0)
                {
                    $count1+=1;
                }
                if($non_billed_hours[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "billable_hours" => $billable_hours,
                "non_billed_hours" => $non_billed_hours,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
        }
        else
        {
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($billable_hours[$i]==0)
                {
                    $count1+=1;
                }
                if($non_billed_hours[$i]==0)
                {
                    $count2+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "billable_hours" => $billable_hours,
                "non_billed_hours" => $non_billed_hours,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
//            return $months[1]->month;
        }

    }

    //productivity all in one

    public function getProductivityAll_perUser(Request $request)
    {
        $available=[];
        $collected=[];
        $billed=[];
        $worked=[];
        $month=[];
        $count1=0;$count2=0;$count3=0;$count4=0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        } elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        }
        $months = SummaryUser::select('month')->whereIn("month",HelperLibrary::getMonthsFromRange($year,true))->distinct()->get();
        if ($user == 'all') {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["available_time", "worked_time", "billed_time", "collected_time"])
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
           for($i=0;$i<sizeof($months);$i++)
           {
//               echo $months[$i]->month;
               $available[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                   ->select(["available_time", "worked_time", "billed_time", "collected_time"])
                   ->where("month", $months[$i]->month)
                   ->orderby("month", "asc")->sum('available_time');
               $collected[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                   ->select(["available_time", "worked_time", "billed_time", "collected_time"])
                   ->where("month", $months[$i]->month)
                   ->orderby("month", "asc")->sum('collected_time');
               $worked[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                   ->select(["available_time", "worked_time", "billed_time", "collected_time"])
                   ->where("month", $months[$i]->month)
                   ->orderby("month", "asc")->sum('worked_time');
                $billed[$i] = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                   ->select(["available_time", "worked_time", "billed_time", "collected_time"])
                   ->where("month", $months[$i]->month)
                   ->orderby("month", "asc")->sum('billed_time');
           }
        } else {
            $data_raw = SummaryUser::where("firm_id", HelperLibrary::getFirmID())
                ->select(["available_time", "worked_time", "billed_time", "collected_time"])->where('user', $user)
                ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
                ->orderby("month", "asc")->get();
        }
        if($user!='all')
        {
            foreach ($data_raw as $k => $v)
            {
                $available[] = $v['available_time'];
                $worked[] = $v['worked_time'];
                $billed[] = $v['billed_time'];
                $collected[] = $v['collected_time'];
            }
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($available[$i]==0)
                {
                    $count1+=1;
                }
                if($worked[$i]==0)
                {
                    $count2+=1;
                }
                if($billed[$i]==0)
                {
                    $count3+=1;
                }
                if($available[$i]==0)
                {
                    $count4+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels) and $count3==sizeof($labels) and $count4==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "available" => $available,
                "billed" => $billed,
                "collected" => $collected,
                "worked" => $worked,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
        }
        else
        {
            for($i=0;$i<sizeof($labels);$i++)
            {
                if($available[$i]==0)
                {
                    $count1+=1;
                }
                if($worked[$i]==0)
                {
                    $count2+=1;
                }
                if($billed[$i]==0)
                {
                    $count3+=1;
                }
                if($available[$i]==0)
                {
                    $count4+=1;
                }
            }
            if($count1==sizeof($labels) and $count2==sizeof($labels) and $count3==sizeof($labels) and $count4==sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
            return response()->json([
                "available" => $available,
                "billed" => $billed,
                "collected" => $collected,
                "worked" => $worked,
//                "months"=>$months[1]->month,
                "labels" => $labels,
                "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                "state" => $request->scope,
                "user" => $user,
                "mt" => $mt,"display"=>$display
            ]);
//            return $months[1]->month;
        }
    }
    // end
    public function utilizationMom(Request $request) {
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
                $this_data = PPTimeEntry::calcUtilization(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
                $last_data = PPTimeEntry::calcUtilization(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", HelperLibrary::getFirmID(), $user, $mt);
            } else {
                $this_data = CLTimeEntry::calcUtilization(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
                $last_data = CLTimeEntry::calcUtilization(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", HelperLibrary::getFirmID(), $user, $mt);
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
    public function utilizationByAttorney(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id",HelperLibrary::getFirmID())->where("type", "Owner (Attorney)")->get();
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
                $data[] = PPTimeEntry::calcUtilization($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLTimeEntry::calcUtilization($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function utilizationByUsers(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id",HelperLibrary::getFirmID())->get();
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
                $data[] = PPTimeEntry::calcUtilization($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLTimeEntry::calcUtilization($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function utilizationByMatterTypes(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $mts = PPMatter::where("firm_id",HelperLibrary::getFirmID())->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        foreach ($mts as $matter_type) {
            $labels[] = $matter_type->matter_type;
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
                $data[] = PPTimeEntry::calcUtilization($monthYear, $type, HelperLibrary::getFirmID(), "all", $matter_type->matter_type);
            } else {
                $data[] = CLTimeEntry::calcUtilization($monthYear, $type, HelperLibrary::getFirmID(), "all", $matter_type->matter_type);
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function utilizationAttorneyVsParalegalStaff(Request $request) {
        $attorney_data = [];
        $paralegal_data = [];
        $labels = [];
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } elseif ($request->scope == 'this-year') {
            $year = Definition::getYearTrail();
            $labels = Definition::getFinancialYearLabels();
        }
        $attorneys = PPUser::where("firm_id",HelperLibrary::getFirmID())->where("type", "Owner (Attorney)")->get();
        $paralegals = PPUser::where("firm_id",HelperLibrary::getFirmID())->where("type", "Paralegal Staff")->get();
        $begin = new \DateTime(substr($year->from, 0, 10));
        $end = new \DateTime(substr($year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $attorney_sum = 0;
            $paralegal_sum = 0;
            foreach ($attorneys as $user) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $attorney_sum += PPTimeEntry::calcUtilization($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user->id, "all");
                } else {
                    $attorney_sum += CLTimeEntry::calcUtilization($i->format("Y-m"), "month",HelperLibrary::getFirmID(), $user->id, "all");
                }
            }
            foreach ($paralegals as $user) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $paralegal_sum += PPTimeEntry::calcUtilization($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user->id, "all");
                } else {
                    $paralegal_sum += CLTimeEntry::calcUtilization($i->format("Y-m"), "month",HelperLibrary::getFirmID(), $user->id, "all");
                }
            }
            $attorney_data[] = $attorney_sum;
            $paralegal_data[] = $paralegal_sum;
        }
        return response()->json([
            "attorney_data" => $attorney_data,
            "paralegal_data" => $paralegal_data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function realizationTrend(Request $request) {
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
            ->select(["realization_rate"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();

        foreach ($data_raw as $k=>$v) {
            $data[] = $v['realization_rate'];
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
    public function realizationMom(Request $request) {
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
                $this_data = PPTimeEntry::calcRealization(date("Y-m", strtotime($i->format("Y-m-01"))), "month", HelperLibrary::getFirmID(), $user, $mt);
                $last_data = PPTimeEntry::calcRealization(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", HelperLibrary::getFirmID(), $user, $mt);
            } else {
                $this_data = CLTimeEntry::calcRealization(date("Y-m", strtotime($i->format("Y-m-01"))), "month",HelperLibrary::getFirmID(), $user, $mt);
                $last_data = CLTimeEntry::calcRealization(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month",HelperLibrary::getFirmID(), $user, $mt);
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
    public function realizationByAttorney(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id",HelperLibrary::getFirmID())->where("type", "Owner (Attorney)")->get();
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
                $data[] = PPTimeEntry::calcRealization($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLTimeEntry::calcRealization($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function realizationByUsers(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id",HelperLibrary::getFirmID())->get();
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
                $data[] = PPTimeEntry::calcRealization($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLTimeEntry::calcRealization($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function realizationByMatterTypes(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $mts = PPMatter::where("firm_id",HelperLibrary::getFirmID())->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        foreach ($mts as $matter_type) {
            $labels[] = $matter_type->matter_type;
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
                $data[] = PPTimeEntry::calcRealization($monthYear, $type, HelperLibrary::getFirmID(), "all", $matter_type->matter_type);
            } else {
                $data[] = CLTimeEntry::calcRealization($monthYear, $type, HelperLibrary::getFirmID(), "all", $matter_type->matter_type);
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function realizationAttorneyVsParalegalStaff(Request $request) {
        $attorney_data = [];
        $paralegal_data = [];
        $labels = [];
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } elseif ($request->scope == 'this-year') {
            $year = Definition::getYearTrail();
            $labels = Definition::getFinancialYearLabels();
        }
        $attorneys = PPUser::where("firm_id",HelperLibrary::getFirmID())->where("type", "Owner (Attorney)")->get();
        $paralegals = PPUser::where("firm_id",HelperLibrary::getFirmID())->where("type", "Paralegal Staff")->get();
        $begin = new \DateTime(substr($year->from, 0, 10));
        $end = new \DateTime(substr($year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $attorney_sum = 0;
            $paralegal_sum = 0;
            foreach ($attorneys as $user) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $attorney_sum += PPTimeEntry::calcRealization($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user->id, "all");
                } else {
                    $attorney_sum += CLTimeEntry::calcRealization($i->format("Y-m"), "month",HelperLibrary::getFirmID(), $user->id, "all");
                }
            }
            foreach ($paralegals as $user) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $paralegal_sum += PPTimeEntry::calcRealization($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user->id, "all");
                } else {
                    $paralegal_sum += CLTimeEntry::calcRealization($i->format("Y-m"), "month",HelperLibrary::getFirmID(), $user->id, "all");
                }
            }
            $attorney_data[] = $attorney_sum;
            $paralegal_data[] = $paralegal_sum;
        }
        return response()->json([
            "attorney_data" => $attorney_data,
            "paralegal_data" => $paralegal_data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function collectionTrend(Request $request) {
        $data = [];
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
            ->select(["collection_rate"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();

        foreach ($data_raw as $k=>$v) {
            $data[] = $v['collection_rate'];
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "mt" => $mt,
        ]);
    }
    public function collectionMom(Request $request) {
        $data = [];
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
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
            "mt" => $mt,
        ]);
    }
    public function revenueTrend(Request $request) {
        $data = [];
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
            "mt" => $mt,
        ]);
    }

    public function collectionByAttorney(Request $request) {
        $data=[];
        $all="";
        $display=0;
        $count=0;
        $collection_mom=[];
        $mom=[];
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
//                    ->select(["collection","collection_mom"])
//                    ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
//                    ->orderby("month", "asc")->get();
//                foreach ($data_raw as $k=>$v) {
//                    $data[] = $v['collection']/1000;
//                    $collection_mom[] =$v["collection_mom"];
//                }

                $all = CLUser::select('id')->where("firm_id",HelperLibrary::getFirmID())->where('type','Attorney')->get();
                for($i=0;$i<sizeof($all);$i++)
                {
                    $all[$i]=$all[$i]->id;
                }
//                return $all;
                for($i=0;$i<sizeof($labels);$i++)
                {
                    $data[$i]= CLInvoice::select('paid')->whereIn('clio_user_id',$all)->where('issued_at',"Like",HelperLibrary::getMonthsFromRange($year,true)[$i]."%")
                        ->where("firm_id",HelperLibrary::getFirmID())
                        ->sum("paid");
                    if($data[$i]!=0)
                    {
                        $data[$i] = round($data[$i]/1000,2);
                    }
                }
//                Mom
                $a=sizeof($data)-1;
                for($i=sizeof($data)-1;$i>=0;$i--)
                {
                    if($i==0)
                    {
                        $current=$data[0];
                        $last= CLInvoice::select('paid')->whereIn('clio_user_id',$all)->where('issued_at',"Like",$mom_date."%")->where('firm_id',HelperLibrary::getFirmID())
                            ->sum("paid");
//                            return $last;
                        if($last!=0)
                        {
                            $collection_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $collection_mom[$i]=0;
                        }
                    }
                    else
                    {
                        $a=$a-1;
                        $current=$data[$i];
                        $last=$data[$a];
                        if($last!=0)
                        {
                            $collection_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $collection_mom[$i]=0;
                        }

                    }

                }
                for($i=0;$i<sizeof($collection_mom);$i++)
                {
                    $mom[$i]=$collection_mom[$i];
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
                    $data[$i]= CLInvoice::select('paid')->where("clio_user_id",$mt)->where('issued_at',"Like",HelperLibrary::getMonthsFromRange($year,true)[$i]."%")
                    ->sum("paid");
                   if($data[$i]!=0)
                   {
                       $data[$i] = $data[$i]/1000;
                   }
                }
//            Mom
                $a=sizeof($data)-1;
                for($i=sizeof($data)-1;$i>=0;$i--)
                {
                    if($i==0)
                    {
                        $current=$data[0];
                        $last= CLInvoice::select('paid')->where("clio_user_id",$mt)->where('issued_at',"Like",$mom_date."%")
                            ->sum("paid");
//                            return $last;
                        if($last!=0)
                        {
                            $collection_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $collection_mom[$i]=0;
                        }
                    }
                    else
                    {
                        $a=$a-1;
                        $current=$data[$i];
                        $last=$data[$a];
                        if($last!=0)
                        {
                            $collection_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $collection_mom[$i]=0;
                        }

                    }

                }
                for($i=0;$i<sizeof($collection_mom);$i++)
                {
                    $mom[$i]=$collection_mom[$i];
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

//        return $users;
    }
    public function collectionByLegalStaff(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id",HelperLibrary::getFirmID())->where("type", "Paralegal Staff")->get();
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
                $data[] = PPInvoice::calcCollection($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLInvoice::calcCollection($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function collectionByUsers(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $users = PPUser::where("firm_id",HelperLibrary::getFirmID())->get();
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
                $data[] = PPInvoice::calcCollection($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            } else {
                $data[] = CLInvoice::calcCollection($monthYear, $type, HelperLibrary::getFirmID(), $user->id, "all");
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function collectionByMatterTypes(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $mts = PPMatter::where("firm_id",HelperLibrary::getFirmID())->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        foreach ($mts as $matter_type) {
            $labels[] = $matter_type->matter_type;
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
                $data[] = PPInvoice::calcCollection($monthYear, $type, HelperLibrary::getFirmID(), "all", $matter_type->matter_type);
            } else {
                $data[] = CLInvoice::calcCollection($monthYear, $type, HelperLibrary::getFirmID(), "all", $matter_type->matter_type);
            }
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "colors" => $colors,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function collectionAttorneyVsParalegalStaff(Request $request) {
        $attorney_data = [];
        $paralegal_data = [];
        $labels = [];
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } elseif ($request->scope == 'this-year') {
            $year = Definition::getYearTrail();
            $labels = Definition::getFinancialYearLabels();
        }
        $attorneys = PPUser::where("firm_id",HelperLibrary::getFirmID())->where("type", "Owner (Attorney)")->get();
        $paralegals = PPUser::where("firm_id",HelperLibrary::getFirmID())->where("type", "Paralegal Staff")->get();
        $begin = new \DateTime(substr($year->from, 0, 10));
        $end = new \DateTime(substr($year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $attorney_sum = 0;
            $paralegal_sum = 0;
            foreach ($attorneys as $user) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $attorney_sum += PPInvoice::calcCollection($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user->id, "all");
                } else {
                    $attorney_sum += CLInvoice::calcCollection($i->format("Y-m"), "month",HelperLibrary::getFirmID(), $user->id, "all");
                }
            }
            foreach ($paralegals as $user) {
                if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                    $paralegal_sum += PPInvoice::calcCollection($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user->id, "all");
                } else {
                    $paralegal_sum += CLInvoice::calcCollection($i->format("Y-m"), "month",HelperLibrary::getFirmID(), $user->id, "all");
                }
            }
            $attorney_data[] = round($attorney_sum, 2);
            $paralegal_data[] = round($paralegal_sum, 2);
        }
        return response()->json([
            "attorney_data" => $attorney_data,
            "paralegal_data" => $paralegal_data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
    public function openVscloseMatters(Request $request)
    {
        $open_count = [];
        $close_count = [];
        $pending_count =[];
        $count1=0;
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
        $begin = new \DateTime(substr($year->from, 0, 10));
        $end = new \DateTime(substr($year->to, 0, 10));
        $count = 0;
        if(HelperLibrary::getFirmIntegration()=="practice_panther")
        {
            for($i = $begin; $i <= $end; $i->modify('+1 month')) {

                $open_count[$count] = PPMatter::select('id')->where("firm_id", HelperLibrary::getFirmID())->where('status', 'open')->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'")->count("id");
                $close_count[$count] = PPMatter::select('id')->where("firm_id", HelperLibrary::getFirmID())->where('status', 'closed')->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'")->count("id");
                if ($open_count[$count] == 0 and $close_count[$count] == 0)
                {
                    $count1+=1;
                }
                $count+=1;
            }

        }
        else
        {
            for($i = $begin; $i <= $end; $i->modify('+1 month')) {
                $open_count[$count] = CLMatter::select('id')->where("firm_id", HelperLibrary::getFirmID())->where('status', 'open')->whereRaw("open_date >= '{$i->format("Y-m-d")}' and open_date <= '{$i->format("Y-m-t")}'")->count("id");
                $close_count[$count] = CLMatter::select('id')->where("firm_id", HelperLibrary::getFirmID())->where('status', 'closed')->whereRaw("close_date >= '{$i->format("Y-m-d")}' and close_date <= '{$i->format("Y-m-t")}'")->count("id");
                $pending_count[$count] = CLMatter::select('id')->where("firm_id", HelperLibrary::getFirmID())->where('status', 'pending')->whereRaw("pending_date >= '{$i->format("Y-m-d")}' and pending_date <= '{$i->format("Y-m-t")}'")->count("id");
                if ($open_count[$count] == 0 and $close_count[$count] == 0 and $pending_count[$count]==0)
                {
                    $count1+=1;
                }
                $count+=1;
            }

        }
        if($count1==sizeof($labels))
        {
            $display=0;
        }
        else
        {
            $display=1;
        }


        return response()->json([
            "open_matters" => $open_count,
            "closed_matters"=>$close_count,
            "pending_matters"=>$pending_count,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,"display"=>$display

        ]);

    }
    public function getEmployeeWrittenoff() {
        $data = SummaryWrittenOffByEmployee::where("firm_id",HelperLibrary::getFirmID())->paginate(HelperLibrary::perPage());
        $data = SummaryWrittenOffByEmployeeResource::collection($data);
        return $data;
    }
}
