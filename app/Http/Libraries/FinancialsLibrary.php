<?php

namespace App\Http\Libraries;

use App\CLInvoice;
use App\CLTimeEntry;
use App\CLUser;
use App\Definition;
use App\PPExpense;
use App\PPInvoice;
use App\PPInvoiceLineItem;
use App\PPTimeEntry;
use App\PPUser;
use App\SummaryMonth;
use Carbon\Carbon;

class FinancialsLibrary
{
    public static function getDescriptive($integration, $state, $user) {
        $data = [];
        if ($state == 'this-year') {
            $year = Definition::getFinancialYear();
        } elseif ($state == 'last-year') {
            $year = Definition::getFinancialYear("last");
        }
        elseif ($state == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
        }
        elseif ($state == 'last-6-months') {
            $year = Definition::getHalfYearTrail();
        }
        elseif ($state == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
        }
        else {
            $year = Definition::getYearTrail();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["revenue", "expense", "collection", "revenue_percentage_growth", "expense_percentage_growth", "collection_percentage_growth", "revenue_avg_rate", "month"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))->orderBy('month','desc')->get();
        foreach ($data_raw as $k=>$v) {
            $revenue = $v['revenue'];
            $expense = $v['expense'];
            $collection = $v['collection'];
            $row = [
                "name" => (new Carbon($v['month']))->format("M, Y"),
                "slug" => (new Carbon($v['month']))->format("Y-m"),
                "revenue" => $revenue,
                "expense" => $expense,
                "collection" => $collection,
                "percentage_growth_revenue" => $v['revenue_percentage_growth'],
                "percentage_growth_expense" => $v['expense_percentage_growth'],
                "percentage_growth_collection" => $v['collection_percentage_growth'],
                "average_rate" => $v['revenue_avg_rate'],
            ];
            $data[] = $row;
        }
        return $data;
    }
    public static function getDiagnostic($integration, $state, $user) {
        $data = [];
        if ($state == 'this-year') {
            $year = Definition::getFinancialYear();
        } elseif ($state == 'last-year') {
            $year = Definition::getFinancialYear("last");
        }
        elseif ($state == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
        }
        elseif ($state == 'last-6-months') {
            $year = Definition::getHalfYearTrail();
        }
        elseif ($state == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
        }else {
            $year = Definition::getYearTrail();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select(["revenue", "billed_time", "revenue_avg_rate", "month"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))->orderBy('month','desc')->get();
        foreach ($data_raw as $k=>$v) {
            $revenue = $v['revenue'];
            $billed = $v['billed_time'];
            if ($integration == "practice_panther") {
                $target = PPUser::calcRevenueTarget((new Carbon($v['month']))->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            } elseif ($integration == "clio") {
                $target = CLUser::calcRevenueTarget((new Carbon($v['month']))->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            }
            $average_rate_target = $billed != 0 ? round($target / $billed, 0) : 0;
            $row = [
                "name" => (new Carbon($v['month']))->format("M, Y"),
                "slug" => (new Carbon($v['month']))->format("Y-m"),
                "revenue" => $revenue,
                "target" => round($target, 0),
                "actual_vs_target" => round($revenue - $target, 1),
                "average_rate" => $v['revenue_avg_rate'],
                "average_rate_target" => $average_rate_target,
                "actual_vs_target_avgr" => round($v['revenue_avg_rate'] - $average_rate_target, 1),
            ];
            $data[] = $row;
        }
        return $data;
    }
    public static function getPredictive($integration, $state, $user) {
        $data = [];
        if ($state == 'this-year') {
            $year = Definition::getFinancialYear();
        } elseif ($state == 'last-year') {
            $year = Definition::getFinancialYear("last");
        }
        elseif ($state == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
        }
        elseif ($state == 'last-6-months') {
            $year = Definition::getHalfYearTrail();
        }
        elseif ($state == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
        }
        else {
            $year = Definition::getYearTrail();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["revenue", "revenue_forecast", "revenue_actual_vs_forecast", "revenue_avg_rate", "revenue_avg_rate_forecast", "revenue_avg_rate_actual_vs_forecast", "month"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))->orderBy('month','desc')->get();
        foreach ($data_raw as $k=>$v) {
            $revenue = round($v['revenue'], 0);
            if ($integration == "practice_panther") {
                $target = PPUser::calcRevenueTarget((new Carbon($v['month']))->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            } elseif ($integration == "clio") {
                $target = CLUser::calcRevenueTarget((new Carbon($v['month']))->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            }
            $row = [
                "name" => (new Carbon($v['month']))->format("M, Y"),
                "slug" => (new Carbon($v['month']))->format("Y-m"),
                "revenue" => $revenue,
                "target" => round($target, 0),
                "forecast" => $v['revenue_forecast'],
                "actual_vs_forecast" => $v['revenue_actual_vs_forecast'],
                "average_rate" => $v['revenue_avg_rate'],
                "average_rate_forecast" => $v['revenue_avg_rate_forecast'],
                "actual_vs_forecast_avgr" => $v['revenue_avg_rate_actual_vs_forecast'],
            ];
            $data[] = $row;
        }
        return $data;
    }
}