<?php

namespace App\Http\Controllers;

use App\CLMatter;
use App\CLPracticeArea;
use App\Exports\MatterTrackerManagerExport;
use App\Http\Libraries\HelperLibrary;
use App\Http\Libraries\MatterLibrary;
use App\Http\Resources\MatterResource;
use App\PPMatter;
use App\PPInvoice;
use App\Definition;
use App\SummaryMatter;
use App\SummaryMatterTracker;
use App\SummaryMonth;
use Illuminate\Http\Request;
use App\Http\Resources\MattersResource;
use Excel;

class MatterController extends Controller
{

    public function trend(Request $request) {
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
            $from = $i->format("Y-m-01");
            $to = $i->format("Y-m-t");
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $rows = PPMatter::where("firm_id", HelperLibrary::getFirmID())
                    ->whereRaw("created_at >= '{$from}' and created_at <= '{$to}'");
                if ($user != "all") {
                    $rows = $rows->whereHas("users", function ($q) use ($user) {
                        $q->where("pp_users.id", $user);
                    });
                }
            } else {
                $rows = CLMatter::where("firm_id", HelperLibrary::getFirmID())
                    ->whereRaw("created_at >= '{$from}' and created_at <= '{$to}'");
                if ($user != "all") {
                    $rows = $rows->whereHas("users", function ($q) use ($user) {
                        $q->where("cl_users.id", $user);
                    });
                }
            }
            if ($mt != "all") {
                $rows = $rows->where("matter_type", $mt);
            }
            $data[] = $rows->count();
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
    public function mom(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
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
            ->select(["matters_mom"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();
        foreach ($data_raw as $k=>$v) {
            $data[] = $v['matters_mom'];
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
                $count = PPMatter::where("matter_type", $v->matter_type)->count();
                $labels[] = $v->matter_type;
            } else {
                $count = CLMatter::where("matter_type", $v->name)->count();
                $labels[] = $v->name;
            }
            $data[] = $count;
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
        $data_raw = SummaryMatter::where("firm_id", HelperLibrary::getFirmID())->where("revenue", "<>", 0)
            ->select(["matter_name", "revenue"])
            ->orderby("revenue", "desc")->limit(10)->get();
        foreach ($data_raw as $k=>$v) {
            $values[] = round($v['revenue']/1000,1);
            $labels[] = $v['matter_name'];
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
        $data_raw = SummaryMatter::where("firm_id", HelperLibrary::getFirmID())->where("outstanding_dues", "<>", 0)
            ->select(["matter_name", "outstanding_dues"])
            ->orderby("outstanding_dues", "desc")->limit(10)->get();
        foreach ($data_raw as $k=>$v) {
            $values[] = round($v['outstanding_dues']/1000,1);
            $labels[] = $v['matter_name'];
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
    public function newMatters(Request $request) {
        $data = [];
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
            ->select(["new_matters"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
            ->orderby("month", "asc")->get();
        foreach ($data_raw as $k=>$v) {
            $data[] = $v['new_matters'];
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
        ]);
    }

    public function new_matters_new(Request $request)
    {
        $count1=0;$count2=0;
        $new_matters = [];
        $new_matters_mom = [];
        $display = 0;
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
        for($i=0;$i<sizeof($labels);$i++)
        {
            $new_matters[$i] = SummaryMonth::select('new_matters')->where('firm_id',HelperLibrary::getFirmID())
                ->where('month',HelperLibrary::getMonthsFromRange($year,true)[$i])->orderby("month", "asc")->get();
            $new_matters_mom[$i] = SummaryMonth::select('matters_mom')->where('firm_id',HelperLibrary::getFirmID())
                ->where('month',HelperLibrary::getMonthsFromRange($year,true)[$i])->orderby("month", "asc")->get();
            if(isset($new_matters[$i][0])){
                $new_matters[$i] = $new_matters[$i][0]->new_matters;
            }
            else{
                $new_matters[$i] = 0;
            }
            if(isset($new_matters_mom[$i][0])){
                $new_matters_mom[$i] = $new_matters_mom[$i][0]->matters_mom;
            }
            else
            {
                $new_matters_mom[$i] = 0;
            }

        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            if($new_matters[$i]==0)
            {
                $count1+=1;
            }
            if($new_matters_mom[$i]==0)
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
            "new_matters" => $new_matters,
            "matters_mom"=>$new_matters_mom,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "display"=>$display
            ]);

    }
    public function timeline ($id) {
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            return new MatterResource(PPMatter::where("firm_id", HelperLibrary::getFirmID())->where("ref_id", $id)->first());
        } else {
            return new MatterResource(CLMatter::where("firm_id", HelperLibrary::getFirmID())->where("ref_id", $id)->first());
        }
    }
    public function all (Request $request) {
        $data = SummaryMatterTracker::where("firm_id", HelperLibrary::getFirmID())
            ->select(["matter_id", "matter_name", "activities", "time_entries", "invoices", "days_file_open", "created_date", "created_date_raw"]);
        if ($request->filled("query")) {
            $data->where("matter_name", "like", "%{$request->get("query")}%");
        }
        if ($request->filled('sort-by') && $request->get("sort-by-type") != "-") {
            $data->orderBy($request->get('sort-by'), $request->get('sort-by-type'));
        }
        return MattersResource::collection($data->paginate(HelperLibrary::perPage()))->additional([
            "meta" => [
                "query" => $request->filled("query") ? $request->get("query") : ""
            ]
        ]);
    }
    public function ragRed (Request $request) {
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        $data = SummaryMatterTracker::where("firm_id", HelperLibrary::getFirmID())
            ->select(["matter_id", "matter_name", "activities", "time_entries", "invoices", "days_file_open", "created_date", "created_date_raw"])
            ->where("type", "red");
        if ($request->filled('sort-by') && $request->get("sort-by-type") != "-") {
            $data->orderBy($request->get('sort-by'), $request->get('sort-by-type'));
        }
        return MattersResource::collection($data->paginate(HelperLibrary::perPage()))->additional([
            "meta" => [
                "filters" => [
                    "state" => "all-time",
                    "user" => $user,
                    "mt" => $mt,
                ]
            ]
        ]);
    }
    public function ragYellow (Request $request) {
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        $data = SummaryMatterTracker::where("firm_id", HelperLibrary::getFirmID())
            ->select(["matter_id", "matter_name", "activities", "time_entries", "invoices", "days_file_open", "created_date", "created_date_raw"])
            ->where("type", "yellow");
        if ($request->filled('sort-by') && $request->get("sort-by-type") != "-") {
            $data->orderBy($request->get('sort-by'), $request->get('sort-by-type'));
        }
        return MattersResource::collection($data->paginate(HelperLibrary::perPage()))->additional([
            "meta" => [
                "filters" => [
                    "state" => "all-time",
                    "user" => $user,
                    "mt" => $mt,
                ]
            ]
        ]);
    }
    public function ragGreen (Request $request) {
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        $data = SummaryMatterTracker::where("firm_id", HelperLibrary::getFirmID())
            ->select(["matter_id", "matter_name", "activities", "time_entries", "invoices", "days_file_open", "created_date", "created_date_raw"])
            ->where("type", "green");
        if ($request->filled('sort-by') && $request->get("sort-by-type") != "-") {
            $data->orderBy($request->get('sort-by'), $request->get('sort-by-type'));
        }
        return MattersResource::collection($data->paginate(HelperLibrary::perPage()))->additional([
            "meta" => [
                "filters" => [
                    "state" => "all-time",
                    "user" => $user,
                    "mt" => $mt,
                ]
            ]
        ]);
    }
    /**
     *  Export Manager data of Matter Tracker section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportManager(Request $request) {
        HelperLibrary::logActivity('User Downloaded Matter Tracker Manager Data');
        $data = SummaryMatterTracker::where("firm_id", HelperLibrary::getFirmID())
            ->select(["matter_id", "matter_name", "activities", "time_entries", "invoices", "days_file_open", "created_date"]);
        if ($request->filled("query")) {
            $data->where("matter_name", "like", "%{$request->get("query")}%");
        }
        $data = $data->get()->map(function ($item, $key) {
            return collect([
                $item->created_date,
                $item->matter_name,
                $item->days_file_open,
                $item->activities,
                $item->time_entries,
                $item->invoices,
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new MatterTrackerManagerExport($data), time()."-matter-tracker-manager.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new MatterTrackerManagerExport($data), time()."-matter-tracker-manager.pdf");
        }
    }
    /**
     *  Export Red data of Matter Tracker section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportRed(Request $request) {
        HelperLibrary::logActivity('User Downloaded Matter Tracker Red Data');
        $data = SummaryMatterTracker::where("firm_id", HelperLibrary::getFirmID())
            ->select(["matter_id", "matter_name", "activities", "time_entries", "invoices", "days_file_open", "created_date"])
            ->where("type", "red")->get();
        $data = $data->map(function ($item, $key) {
            return collect([
                $item->created_date,
                $item->matter_name,
                $item->days_file_open,
                $item->activities,
                $item->time_entries,
                $item->invoices,
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new MatterTrackerManagerExport($data), time()."-matter-tracker-red.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new MatterTrackerManagerExport($data), time()."-matter-tracker-red.pdf");
        }
    }
    /**
     *  Export Yellow data of Matter Tracker section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportYellow(Request $request) {
        HelperLibrary::logActivity('User Downloaded Matter Tracker Yellow Data');
        $data = SummaryMatterTracker::where("firm_id", HelperLibrary::getFirmID())
            ->select(["matter_id", "matter_name", "activities", "time_entries", "invoices", "days_file_open", "created_date"])
            ->where("type", "yellow")->get();
        $data = $data->map(function ($item, $key) {
            return collect([
                $item->created_date,
                $item->matter_name,
                $item->days_file_open,
                $item->activities,
                $item->time_entries,
                $item->invoices,
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new MatterTrackerManagerExport($data), time()."-matter-tracker-yellow.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new MatterTrackerManagerExport($data), time()."-matter-tracker-yellow.pdf");
        }
    }
    /**
     *  Export Green data of Matter Tracker section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportGreen(Request $request) {
        HelperLibrary::logActivity('User Downloaded Matter Tracker Green Data');
        $data = SummaryMatterTracker::where("firm_id", HelperLibrary::getFirmID())
            ->select(["matter_id", "matter_name", "activities", "time_entries", "invoices", "days_file_open", "created_date"])
            ->where("type", "green")->get();
        $data = $data->map(function ($item, $key) {
            return collect([
                $item->created_date,
                $item->matter_name,
                $item->days_file_open,
                $item->activities,
                $item->time_entries,
                $item->invoices,
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new MatterTrackerManagerExport($data), time()."-matter-tracker-green.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new MatterTrackerManagerExport($data), time()."-matter-tracker-green.pdf");
        }
    }
}
