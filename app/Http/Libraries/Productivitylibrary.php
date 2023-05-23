<?php

namespace App\Http\Libraries;


use App\CLInvoice;
use App\CLTimeEntry;
use App\CLUser;
use App\Definition;
use App\PPInvoice;
use App\PPInvoiceLineItem;
use App\PPTimeEntry;
use App\PPUser;
use App\SummaryMonth;
use Carbon\Carbon;

class ProductivityLibrary
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
        }else {
            $year = Definition::getYearTrail();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["month", "available_time", "worked_time", "billed_time", "collected_time", "billed_vs_collected"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))->orderBy('month','desc')->get();
        foreach ($data_raw as $k=>$v) {
            $row = [
                "name" => (new Carbon($v['month']))->format("M, Y"),
                "slug" => (new Carbon($v['month']))->format("Y-m"),
                "available" => $v['available_time'],
                "worked" => $v['worked_time'],
                "billed" => $v['billed_time'],
                "collected" => $v['collected_time'],
                "billed_vs_collected" => $v['billed_vs_collected'],
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
        }
        else {
            $year = Definition::getYearTrail();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["month", "available_time", "worked_time", "billed_time", "collected_time"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))->orderBy('month','desc')->get();
        foreach ($data_raw as $k=>$v) {
            if ($integration == "practice_panther") {
                $target = PPUser::calcAvailableTimeTarget((new Carbon($v['month']))->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            } elseif ($integration == "clio") {
                $target = CLUser::calcAvailableTimeTarget((new Carbon($v['month']))->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            }
            $row = [
                "name" => (new Carbon($v['month']))->format("M, Y"),
                "slug" => (new Carbon($v['month']))->format("Y-m"),
                "available" => $v['available_time'],
                "worked" => $v['worked_time'],
                "billed" => $v['billed_time'],
                "collected" => $v['collected_time'],
                "target" => $target,
                "deviation" => round($v['billed_time'] - $target, 1),
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
        }else {
            $year = Definition::getYearTrail();
        }
        $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
            ->select(["month", "available_time", "worked_time", "billed_time", "collected_time", "billed_time_forecast", "billed_actual_vs_forecast"])
            ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))->orderBy('month','desc')->get();
        foreach ($data_raw as $k=>$v) {
            if ($integration == "practice_panther") {
                $target = PPUser::calcAvailableTimeTarget((new Carbon($v['month']))->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            } elseif ($integration == "clio") {
                $target = CLUser::calcAvailableTimeTarget((new Carbon($v['month']))->format("Y-m"), "month", HelperLibrary::getFirmID(), $user);
            }
            $row = [
                "name" => (new Carbon($v['month']))->format("M, Y"),
                "slug" => (new Carbon($v['month']))->format("Y-m"),
                "available" => $v['available_time'],
                "worked" => $v['worked_time'],
                "billed" => $v['billed_time'],
                "collected" => $v['collected_time'],
                "target" => $target,
                "deviation_target" => round($v['billed_time'] - $target, 1),
                "forecast" => $v['billed_time_forecast'],
                "deviation_forecast" => $v['billed_actual_vs_forecast'],
            ];
            $data[] = $row;
        }
        return $data;
    }
}