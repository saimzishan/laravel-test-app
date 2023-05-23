<?php

namespace App\Http\Controllers;

use App\CLContact;
use App\CLMatter;
use App\CLPracticeArea;
use App\Definition;
use App\Http\Libraries\HelperLibrary;
use App\PPContact;
use App\PPMatter;
use App\SummaryClient;
use App\SummaryMonth;
use App\SummaryUser;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function trend(Request $request) {
        $data = [];
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
        }  elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } else {
            $financial_year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        } 
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            $from = $i->format("Y-m-01");
            $to = $i->format("Y-m-t");
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $rows = PPContact::where("firm_id", HelperLibrary::getFirmID())
                    ->whereHas("account", function($q)use($from, $to){
                        $q->whereRaw("created_at >= '{$from}' and created_at <= '{$to}'");
                    });
                if ($mt != "all") {
                    $rows = $rows->whereHas("account", function($q)use($mt){
                        $q->whereHas("matters", function($q1)use($mt){
                            $q1->where("matter_type", $mt);
                        });
                    });
                }
            } else {
                $rows = CLContact::where("firm_id", HelperLibrary::getFirmID())
                    ->whereRaw("created_at >= '{$from}' and created_at <= '{$to}'");
                if ($mt != "all") {
                    $rows->whereHas("matters", function($q1)use($mt){
                        $q1->where("matter_type", $mt);
                    });
                }
            }
            $data[] = $rows->count();
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "mt" => $mt,
        ]);
    }
    public function mom(Request $request) {
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
            ->select(["clients_mom"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();
        foreach ($data_raw as $k=>$v) {
            $data[] = $v['clients_mom'];
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "mt" => $mt,
        ]);
    }
    public function mts(Request $request) {
        $data = [];
        $colors = [];
        $labels = [];
        $colorset = ["#de4d44", "#9e3744", "#ff842a", "#fc766a", "#c83e74", "#8d9440", "#fed65e", "#2e5d9f", "#755841", "#daa03d", "#616247", "#e7b7cf"];
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $mts = PPMatter::where("firm_id", HelperLibrary::getFirmID())->where("matter_type", "<>", null)->select('matter_type')->distinct()->get();
        } else {
            $mts = CLPracticeArea::where("firm_id", HelperLibrary::getFirmID())->select('name')->distinct()->get();
        }
        foreach ($mts as $v) {
            $colors[] = $colorset[array_rand($colorset)];
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $matters = PPMatter::where("matter_type", $v->matter_type)->get();
            } else {
                $matters = CLMatter::where("matter_type", $v->name)->get();
            }
            $count = 0;
            foreach ($matters as $matter) {
                $count += $matter->getContactsCount();
            }
            $data[] = $count;
            $labels[] = $v->matter_type;
        }
        return response()->json([
            "data" => $data,
            "colors" => $colors,
            "labels" => $labels,
            "scope" => "All Time",
            "state" => "all-time",
        ]);
    }
    public function top10ByRevenue(Request $request) {
        $labels = [];
        $values = [];
        $count1 = 0;
        $data_raw = SummaryClient::where("firm_id", HelperLibrary::getFirmID())->where("revenue", "<>", 0)
            ->select(["client_name", "revenue"])
            ->orderby("revenue", "desc")->get();
        foreach ($data_raw as $k=>$v) {
            $values[] = round($v['revenue']/1000,1);
            $labels[] = $v['client_name'];
        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            if($values[$i]==0)
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
            "data" => $values,
            "labels" => $labels,
            "scope" => "All Time",
            "state" => "all-time",
            "display"=>$display
        ]);
    }
    public function top10ByOutstanding(Request $request) {
        $labels = [];
        $values = [];
        $count1=0;
        $data_raw = SummaryClient::where("firm_id", HelperLibrary::getFirmID())->where("outstanding_dues", "<>", 0)
            ->select(["client_name", "outstanding_dues"])
            ->orderby("outstanding_dues", "desc")->limit(10)->get();
        foreach ($data_raw as $k=>$v) {
            $values[] = round($v['outstanding_dues']/1000,1);
            $labels[] = $v['client_name'];
        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            if($values[$i]==0)
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
            "data" => $values,
            "labels" => $labels,
            "scope" => "All Time",
            "state" => "all-time",
            "display"=>$display
        ]);
    }
    public function newClients(Request $request) {
        $data = [];
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
            ->select(["new_clients"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();
        foreach ($data_raw as $k=>$v) {
            $data[] = $v['new_clients'];
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }
     public function new_clients_new(Request $request)
 {
     $count1=0;$count2=0;
     $clients = [];
     $clients_mom = [];
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
     for($i=0;$i<sizeof($labels);$i++)
     {
         $clients[$i] = SummaryMonth::select('new_clients')->where('firm_id',HelperLibrary::getFirmID())
             ->where('month',HelperLibrary::getMonthsFromRange($year,true)[$i])->orderby("month","asc")->get();
         if(isset($clients[$i][0]))
         {
             $clients[$i] = $clients[$i][0]->new_clients;
         }
         else
         {
             $clients[$i] = 0;
         }
         $clients_mom[$i] = SummaryMonth::select('clients_mom')->where('firm_id',HelperLibrary::getFirmID())
             ->where('month',HelperLibrary::getMonthsFromRange($year,true)[$i])->orderby("month","asc")->get();
         if(isset($clients_mom[$i][0]))
         {
             $clients_mom[$i] = $clients_mom[$i][0]->clients_mom;
         }
         else
         {
             $clients_mom[$i] = 0;
         }
     }
     for($i=0;$i<sizeof($labels);$i++)
     {
         if($clients[$i]==0)
         {
             $count1+=1;
         }
         if($clients_mom[$i]==0)
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
         "clients" => $clients,
         "clients_mom" => $clients_mom,
         "labels" => $labels,
         "scope" => ucwords(str_replace('-', ' ', $request->scope)),
         "state" => $request->scope,
         "display"=>$display
     ]);


 }
}

