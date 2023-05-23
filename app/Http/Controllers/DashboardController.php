<?php

namespace App\Http\Controllers;

use App\CLInvoice;
use App\CLTimeEntry;
use App\CLUser;
use App\Firm;
use App\PPInvoiceLineItem;
use App\PPUser;
use App\SummaryAllTime;
use App\SummaryMonth;
use App\User;
use App\CLMatter;
use App\FirmUser;
use App\PPMatter;
use App\CLContact;
use App\PPContact;
use App\PPExpense;
use App\PPInvoice;
use App\Definition;
use App\PPTimeEntry;
use Illuminate\Http\Request;
use App\Http\Libraries\HelperLibrary;
use mysql_xdevapi\Exception;

class DashboardController extends Controller
{

    public function index() {

        return response()->json([
            "users" => User::where("is_delete", 0)->count(),
            "firms" => Firm::where("is_delete", 0)->count(),
            "firm_users" => FirmUser::where("is_delete", 0)->count(),
        ]);
    }
    /* Deprecated */
    /*public function getContactsData(Request $request) {
        $ts = '';
        $ls = '';
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'today') {
            $ts = "LEFT(created_at, 10) = '".date("Y-m-d")."'";
            $ls = "LEFT(created_at, 10) = '".date("Y-m-d", strtotime("-1 day"))."'";
        } elseif ($request->scope == 'this-month') {
            $ts = "LEFT(created_at, 7) = '".date("Y-m")."'";
            $ls = "LEFT(created_at, 7) = '".date("Y-m", strtotime("-1 month"))."'";
        } elseif ($request->scope == 'last-month') {
            $ts = "LEFT(created_at, 7) = '".date("Y-m", strtotime("-1 month"))."'";
            $ls = "LEFT(created_at, 7) = '".date("Y-m", strtotime("-2 month"))."'";
        } elseif ($request->scope == 'this-year') {
            $year_this = Definition::getFinancialYear();
            $year_last = Definition::getFinancialYear("last");
            $ts = "created_at >= '{$year_this->from}' and created_at <= '{$year_this->to}'";
            $ls = "created_at >= '{$year_last->from}' and created_at <= '{$year_last->to}'";
        } elseif ($request->scope == 'last-year') {
            $year_last = Definition::getFinancialYear("last");
            $year_before = Definition::getFinancialYear("before-last");
            $ts = "created_at >= '{$year_last->from}' and created_at <= '{$year_last->to}'";
            $ls = "created_at >= '{$year_before->from}' and created_at <= '{$year_before->to}'";
        }
        if(HelperLibrary::getFirmIntegration()=='practice_panther')
        {
            $this_data = PPContact::where("firm_id", HelperLibrary::getFirmID())
                ->whereHas("account", function($q)use($ts){
                    $q->whereRaw($ts);
                });
            $last_data = PPContact::where("firm_id", HelperLibrary::getFirmID())
                ->whereHas("account", function($q)use($ls){
                    $q->whereRaw($ls);
                });
        }else
        {
            $this_data = CLContact::where("firm_id", HelperLibrary::getFirmID())->whereRaw($ts);
            $last_data = CLContact::where("firm_id", HelperLibrary::getFirmID())->whereRaw($ls);
            
        }
        if ($mt != "all") {
            $this_data = $this_data->whereHas("account", function($q)use($mt){
                $q->whereHas("matters", function($q1)use($mt){
                    $q1->where("matter_type", $mt);
                });
            });
            $last_data = $last_data->whereHas("account", function($q)use($mt){
                $q->whereHas("matters", function($q1)use($mt){
                    $q1->where("matter_type", $mt);
                });
            });
        }
        $this_data = $this_data->count();
        $last_data = $last_data->count();
        if ($last_data != 0) {
            $result = (($this_data - $last_data) / $last_data) * 100;
        } else {
            $result = 0;
        }
        return response()->json([
            "count" => $this_data,
            "percentage" => $result < 0 ? $result * -1 : $result,
            "icon" => $result < 0 ? 'fa-arrow-down' : 'fa-arrow-up',
            "color" => $result < 0 ? 'text-danger' : 'text-success',
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }*/
    /* Deprecated */
    /*public function getMattersData(Request $request) {
        $ts = '';
        $ls = '';
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'today') {
            $ts = "LEFT(created_at, 10) = '".date("Y-m-d")."'";
            $ls = "LEFT(created_at, 10) = '".date("Y-m-d", strtotime("-1 day"))."'";
        } elseif ($request->scope == 'this-month') {
            $ts = "LEFT(created_at, 7) = '".date("Y-m")."'";
            $ls = "LEFT(created_at, 7) = '".date("Y-m", strtotime("-1 month"))."'";
        } elseif ($request->scope == 'last-month') {
            $ts = "LEFT(created_at, 7) = '".date("Y-m", strtotime("-1 month"))."'";
            $ls = "LEFT(created_at, 7) = '".date("Y-m", strtotime("-2 month"))."'";
        } elseif ($request->scope == 'this-year') {
            $year_this = Definition::getFinancialYear();
            $year_last = Definition::getFinancialYear("last");
            $ts = "created_at >= '{$year_this->from}' and created_at <= '{$year_this->to}'";
            $ls = "created_at >= '{$year_last->from}' and created_at <= '{$year_last->to}'";
        } elseif ($request->scope == 'last-year') {
            $year_last = Definition::getFinancialYear("last");
            $year_before = Definition::getFinancialYear("before-last");
            $ts = "created_at >= '{$year_last->from}' and created_at <= '{$year_last->to}'";
            $ls = "created_at >= '{$year_before->from}' and created_at <= '{$year_before->to}'";
        }
        if(HelperLibrary::getFirmIntegration()=='practice_panther')
        {
        $this_data = PPMatter::where("firm_id", HelperLibrary::getFirmID())
            ->whereRaw($ts)->whereHas("users", function($q)use($user){
                $q->where('can_be_calculated', true);
            });
        $last_data = PPMatter::where("firm_id", HelperLibrary::getFirmID())
            ->whereRaw($ls)->whereHas("users", function($q)use($user){
                $q->where('can_be_calculated', true);
            });
        } else
        {
            $this_data = CLMatter::where("firm_id", HelperLibrary::getFirmID())
            ->whereRaw($ts)->whereHas("users", function($q)use($user){
                $q->where('can_be_calculated', true);
            });
        $last_data = CLMatter::where("firm_id", HelperLibrary::getFirmID())
            ->whereRaw($ls)->whereHas("users", function($q)use($user){
                $q->where('can_be_calculated', true);
            });
        }    
        if ($user != "all") {
            $this_data = $this_data->whereHas("users", function($q)use($user){
                $q->where("pp_users.id", $user);
            });
        }
        if ($mt != "all") {
            $this_data = $this_data->where("matter_type", $mt);
            $last_data = $last_data->where("matter_type", $mt);
        }
        $this_data = $this_data->count();
        $last_data = $last_data->count();
        if ($last_data != 0) {
            $result = (($this_data - $last_data) / $last_data) * 100;
        } else {
            $result = 0;
        }
        return response()->json([
            "count" => $this_data,
            "percentage" => $result < 0 ? $result * -1 : $result,
            "icon" => $result < 0 ? 'fa-arrow-down' : 'fa-arrow-up',
            "color" => $result < 0 ? 'text-danger' : 'text-success',
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }*/
    public function getFinancialsData(Request $request) {
        $display=0;
        if ($request->scope == 'this-year') {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
        } elseif ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels("last");
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
        } elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrail();
            $labels = Definition::getHalfYearTrailLabels();
        }
        $count1=0;$count2=0;$count3=0;
        $data = ["revenue"=>[],"expense"=>[],"collection"=>[]];
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select(["revenue", "expense", "collection"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))->get();
        foreach ($data_raw as $v) {
            $data["revenue"][] = round($v['revenue'] / 1000, 0);
            $data["expense"][] = round($v['expense'] / 1000, 0);
            $data["collection"][] = round($v['collection'] / 1000, 0);
        }
            for ($i = 0; $i < sizeof($labels); $i++) {
                if (isset($data["revenue"][$i]) and $data["revenue"][$i] == 0)
                {
                    $count1 += 1;
                }
                if (isset($data["expense"][$i]) and $data["expense"][$i] == 0) {
                    $count2 += 1;
                }
                if (isset($data["collection"][$i]) and $data["collection"][$i] == 0) {
                    $count3 += 1;
                }
            }
        if($count1 == sizeof($labels) and $count2 == sizeof($labels) and $count3 == sizeof($labels))
        {
            $display = 0;
        }
        elseif($count1 == 0 and $count2 == 0 and $count3 == 0 and sizeof($data['revenue'])==0 and sizeof($data['expense'])==0 and sizeof($data['collection'])==0)
        {
            $display = 0;
        }
        else
        {
            $display = 1;
        }

        HelperLibrary::logActivity('User Viewed Financials Chart at Dashboard');
        return response()->json([
            "data" => $data,
            "labels" => $labels,"display"=>$display
        ]);
    }
    public function getARData(Request $request) {
        $ts = '';
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'this-month') {
            $ts = "LEFT(created_at, 7) = '".date("Y-m")."'";
        } elseif ($request->scope == 'last-month') {
            $ts = "LEFT(created_at, 7) = '".date("Y-m", strtotime("-1 month"))."'";
        } elseif ($request->scope == 'this-year') {
            $year_this = Definition::getFinancialYear();
            $ts = "created_at >= '{$year_this->from}' and created_at <= '{$year_this->to}'";
        } elseif ($request->scope == 'last-year') {
            $year_last = Definition::getFinancialYear("last");
            $ts = "created_at >= '{$year_last->from}' and created_at <= '{$year_last->to}'";
        } else {
            $request->scope = "all-time";
        }
        $data_raw = SummaryAllTime::where("firm_id", HelperLibrary::getFirmID())
            ->select(["ar_current", "ar_late", "ar_delinquent", "ar_collection"])->first();
        $total = $data_raw['ar_current'] + $data_raw['ar_late'] + $data_raw['ar_delinquent'] + $data_raw['ar_collection'];
        HelperLibrary::logActivity('User Viewed AR Aging Summary Chart at Dashboard');
        if($data_raw['ar_current']==0 and $data_raw['ar_late']==0 and $data_raw['ar_collection']==0 and $data_raw['ar_delinquent']==0)
        {
            $display = 0;
        }
        else
        {
            $display = 1;
        }
        return response()->json([
            "current" => [
                "percentage" => $total != 0 ? round(($data_raw['ar_current'] / $total) * 100, 2): 0,
                "value" => $data_raw['ar_current'],
            ],
            "late" => [
                "percentage" => $total != 0 ? round(($data_raw['ar_late'] / $total) * 100, 2) : 0,
                "value" => $data_raw['ar_late'],
            ],
            "delinquent" => [
                "percentage" => $total != 0 ? round(($data_raw['ar_delinquent'] / $total) * 100, 2) : 0,
                "value" => $data_raw['ar_delinquent'],
            ],
            "collection" => [
                "percentage" => $total != 0 ? round(($data_raw['ar_collection'] / $total) * 100, 2) : 0,
                "value" => $data_raw['ar_collection'],
            ],
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
            "display"=>$display
        ]);
    }
    /* Deprecated */
    /*public function getUtilizationData(Request $request) {
        $this_data = 0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'today') {
            $year_this = Definition::getYearTrail();
            $this_data = PPTimeEntry::calcUtilization(date("Y-m-d"), "today", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPTimeEntry::calcUtilizationYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'this-month') {
            $year_this = Definition::getYearTrail();
            $this_data = PPTimeEntry::calcUtilization(date("Y-m"), "month", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPTimeEntry::calcUtilizationYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'last-month') {
            $year_this = Definition::getYearTrail();
            $this_data = PPTimeEntry::calcUtilization(date("Y-m", strtotime("-1 month")), "month", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPTimeEntry::calcUtilizationYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'this-year') {
            $year_this = Definition::getYearTrail();
            $this_data = PPTimeEntry::calcUtilization($year_this, "year", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPTimeEntry::calcUtilizationYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'last-year') {
            $year_last = Definition::getFinancialYear("last");
            $this_data = PPTimeEntry::calcUtilization($year_last, "year", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPTimeEntry::calcUtilizationYearWise($year_last, HelperLibrary::getFirmID(), $user, $mt);
        }
        return response()->json([
            "percentage" => $this_data,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "vals" => $vals,
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }*/
    /* Deprecated */
    /*public function getRealizationData(Request $request) {
        $this_data = 0;
        $vals = 0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'today') {
            $year_this = Definition::getYearTrail();
            $this_data = PPTimeEntry::calcRealization(date("Y-m-d"), "today", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPTimeEntry::calcRealizationYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'this-month') {
            $year_this = Definition::getYearTrail();
            $this_data = PPTimeEntry::calcRealization(date("Y-m"), "month", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPTimeEntry::calcRealizationYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'last-month') {
            $year_this = Definition::getYearTrail();
            $this_data = PPTimeEntry::calcRealization(date("Y-m", strtotime("-1 month")), "month", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPTimeEntry::calcRealizationYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'this-year') {
            $year_this = Definition::getYearTrail();
            $this_data = PPTimeEntry::calcRealization($year_this, "year", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPTimeEntry::calcRealizationYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'last-year') {
            $year_last = Definition::getFinancialYear("last");
            $this_data = PPTimeEntry::calcRealization($year_last, "year", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPTimeEntry::calcRealizationYearWise($year_last, HelperLibrary::getFirmID(), $user, $mt);
        }
        return response()->json([
            "percentage" => $this_data,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "vals" => $vals,
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }*/
    /* Deprecated */
    /*public function getCollectionData(Request $request) {
        $this_data = 0;
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'today') {
            $year_this = Definition::getYearTrail();
            $this_data = PPInvoice::calcCollection(date("Y-m-d"), "today", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPInvoice::calcCollectionYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'this-month') {
            $year_this = Definition::getYearTrail();
            $this_data = PPInvoice::calcCollection(date("Y-m"), "month", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPInvoice::calcCollectionYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'last-month') {
            $year_this = Definition::getYearTrail();
            $this_data = PPInvoice::calcCollection(date("Y-m", strtotime("-1 month")), "month", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPInvoice::calcCollectionYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'this-year') {
            $year_this = Definition::getYearTrail();
            $this_data = PPInvoice::calcCollection($year_this, "year", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPInvoice::calcCollectionYearWise($year_this, HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($request->scope == 'last-year') {
            $year_last = Definition::getFinancialYear("last");
            $this_data = PPInvoice::calcCollection($year_last, "year", HelperLibrary::getFirmID(), $user, $mt);
            $vals = PPInvoice::calcCollectionYearWise($year_last, HelperLibrary::getFirmID(), $user, $mt);
        }
        return response()->json([
            "percentage" => $this_data,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "vals" => $vals,
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }*/
    /* Deprecated */
    /*public function getURCData(Request $request) {
        $data = [];
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'this-year') {
            $year = Definition::getYearTrail();
        } elseif ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
        }
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $data["available"] = round(PPUser::calcAvailableHourYearWise($year, HelperLibrary::getFirmID(), $user), 0);
            $data["utilization"] = round(PPTimeEntry::calcUtilizationYearWiseAverageHours($year, HelperLibrary::getFirmID(), $user, $mt), 0);
            $data["realization"] = round(PPInvoiceLineItem::calcTotalBilledHoursYearWise($year, HelperLibrary::getFirmID(), $user), 0);
            $data["collection"] = round(PPInvoice::calcTotalCollectedHoursAverageYearWise($year, HelperLibrary::getFirmID(), $user, $mt), 0);
        } else {
            $data["available"] = round(CLUser::calcAvailableHourYearWise($year, HelperLibrary::getFirmID(), $user), 0);
            $data["utilization"] = round(CLTimeEntry::calcUtilizationYearWiseAverageHours($year, HelperLibrary::getFirmID(), $user, $mt), 0);
            $data["realization"] = round(CLTimeEntry::calcTotalBilledHoursYearWise($year, HelperLibrary::getFirmID(), $user, $mt), 0);
            $data["collection"] = round(CLInvoice::calcTotalCollectedHoursAverageYearWise($year, HelperLibrary::getFirmID(), $user, $mt), 0);
        }
        HelperLibrary::logActivity('User Viewed Productivity Chart at Dashboard');
        return response()->json([
            "data" => $data,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,
            "user" => $user,
            "mt" => $mt,
        ]);
    }*/
    public function getProductivityData(Request $request) {

        $value = 1;
        if ($request->scope == 'this-year') {
            $year = Definition::getYearTrail();
            $value = 12;
        } elseif ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $value = 12;
        } elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $value = 12;
        } elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrail();
            $value = 6;
        }
        $data = ["available"=>0, "worked"=>0, "billed"=>0, "collected"=>0];
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select(["available_time", "worked_time", "billed_time", "collected_time"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))->get();
        foreach ($data_raw as $v) {
            $data["available"] += $v['available_time'];
            $data["worked"] += $v['worked_time'];
            $data["billed"] += $v['billed_time'];
            $data["collected"] += $v['collected_time'];
        }
        if ($value != 0) {
            $data["available"] = $data["available"]/$value;
            $data["worked"] = $data["worked"]/$value;
            $data["billed"] = $data["billed"]/$value;
            $data["collected"] = $data["collected"]/$value;
        } else {
            $data["available"] = 0;
            $data["worked"] = 0;
            $data["billed"] = 0;
            $data["collected"] = 0;
        }
        if($data["available"]==0 and $data["worked"]==0 and $data["billed"]==0 and $data["collected"]==0)
        {
            $display=0;
        }
        else{
            $display=1;
        }

        HelperLibrary::logActivity('User Viewed Productivity Chart at Dashboard');
        return response()->json([
            "data" => $data,"display"=>$display
        ]);
    }
    public function getMatterTrackerData(Request $request) {
        $user = $request->filled("user") ? $request->user : "all";
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        $data_raw = SummaryAllTime::where("firm_id", HelperLibrary::getFirmID())->select(["matters_red", "matters_yellow", "matters_green"])->first();
        $total = $data_raw['matters_red'] + $data_raw['matters_yellow'] + $data_raw['matters_green'];
        HelperLibrary::logActivity('User Viewed Matter Tracker at Dashboard');
        if($data_raw["matters_red"]==0 and $data_raw["matters_yellow"]==0 and $data_raw["matters_green"]==0)
        {
            $display = 0;
        }
        else
        {
            $display = 1;
        }

        return response()->json([
            "red" => [
                "count" => $data_raw['matters_red'],
                "progress" => $total != 0 ? round(($data_raw['matters_red'] / $total) * 100, 0) : 0,
            ],
            "yellow" => [
                "count" => $data_raw['matters_yellow'],
                "progress" => $total != 0 ? round(($data_raw['matters_yellow'] / $total) * 100, 0) : 0,
            ],
            "green" => [
                "count" => $data_raw['matters_green'],
                "progress" => $total != 0 ? round(($data_raw['matters_green'] / $total) * 100, 0) : 0,
            ],
            "scope" => "All Time",
            "state" => "all-time",
            "user" => $user,
            "mt" => $mt,
            "display"=>$display
        ]);
    }

}
