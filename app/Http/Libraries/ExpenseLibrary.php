<?php

namespace App\Http\Libraries;


use App\CLInvoice;
use App\CLMatter;
use App\CLTimeEntry;
use App\Definition;
use App\PPExpense;
use App\PPInvoice;
use App\PPMatter;
use App\PPTimeEntry;

class ExpenseLibrary
{
    public static function getList($integration, $state, $mt) {
        $data = [];
        if ($state == 'this-year') {
            $financial_year = Definition::getFinancialYear();
        } elseif ($state == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
        } else {
            $financial_year = Definition::getYearTrail();
        }
        $begin = new \DateTime(substr($financial_year->from, 0, 10));
        $end = new \DateTime(substr($financial_year->to, 0, 10));
        if ($integration == "practice_panther") {
            $old_amount = PPExpense::calcExpense((clone $begin)->modify("-1 month")->format("Y-m"), "month", HelperLibrary::getFirmID(), "all", $mt);
        } elseif ($integration == "clio") {
            $old_amount = CLTimeEntry::calcExpense((clone $begin)->modify("-1 month")->format("Y-m"), "month", HelperLibrary::getFirmID(), "all", $mt);
        }
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if ($integration == "practice_panther") {
                $revenue = PPInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), "all", $mt);
                $expense = PPExpense::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), "all", $mt);
            } elseif ($integration == "clio") {
                $revenue = CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), "all", $mt);
                $expense = CLTimeEntry::calcExpense($i->format("Y-m"), "month", HelperLibrary::getFirmID(), "all", $mt);
            }
            $data[] = [
                "name" => $i->format("Y - F"),
                "slug" => $i->format("Y-m"),
                "amount" => number_format($expense),
                "amount_raw" => $expense,
                "percentage_to_sale" => $revenue==0 ? 0 : number_format(($expense/$revenue) * 100)
            ];
        }
        foreach ($data as $index=>$row) {
            $this_data = str_replace(",", "", $data[$index]['amount']);
            if ($index == 0) {
                $data[$index]['percentage_growth'] = $old_amount == 0 ? 0 : (($this_data - $old_amount) / $old_amount) * 100;
            } else {
                $last_data = str_replace(",", "", $data[$index - 1]['amount']);
                $data[$index]['percentage_growth'] = $last_data == 0 ? 0 : (($this_data - $last_data) / $last_data) * 100;
            }
            $data[$index]['percentage_growth'] = round($data[$index]['percentage_growth'], 2);
        }
        return $data;
    }
    public static function getSingle($integration, $month) {
        $data = [];
        if ($integration == "practice_panther") {
            $matters = PPMatter::where("firm_id", HelperLibrary::getFirmID())
                ->whereHas("invoices", function($q) use ($month) {
                    $q->whereRaw("LEFT(pp_invoices.created_at, 7) = '{$month}'");
                })->get();
        } elseif ($integration == "clio") {
            $matters = CLMatter::where("firm_id", HelperLibrary::getFirmID())
                ->whereHas("invoices", function($q) use ($month) {
                    $q->whereRaw("LEFT(cl_invoices.created_at, 7) = '{$month}'");
                })->get();
        }
        $total = 0;
        foreach ($matters as $mt) {
            if ($integration == "practice_panther") {
                $amount = PPExpense::calcExpense($month, "month", HelperLibrary::getFirmID(), "all", "all", $mt->id);
            } elseif ($integration == "clio") {
                $amount = CLTimeEntry::calcExpense($month, "month", HelperLibrary::getFirmID(), "all", "all", $mt->id);
            }
            $row = [
                "name" => $mt->getName(),
                "amount" => $amount
            ];
            $total += $row['amount'];
            $row['amount'] = number_format($row['amount']);
            $data[] = $row;
        }
        return (object) ["data" => $data, "total" => $total];
    }
}