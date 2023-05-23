<?php

namespace App\Http\Libraries;

use App\CLContact;
use App\CLInvoice;
use App\CLMatter;
use App\CLTimeEntry;
use App\Definition;
use App\PPInvoice;
use App\PPMatter;
use App\PPTimeEntry;

class RevenueLibrary
{
    public static function getList($integration, $state, $user, $mt) {
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
            $old_amount = PPInvoice::calcRevenue((clone $begin)->modify("-1 month")->format("Y-m"), "month", HelperLibrary::getFirmID(), $user, $mt);
        } elseif ($integration == "clio") {
            $old_amount = CLInvoice::calcRevenue((clone $begin)->modify("-1 month")->format("Y-m"), "month", HelperLibrary::getFirmID(), $user, $mt);
        }
        for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
            if ($integration == "practice_panther") {
                $amount = PPInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user, $mt);
                $time_entries = PPTimeEntry::getTotalBillableHours($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user, $mt);
            } elseif ($integration == "clio") {
                $amount = CLInvoice::calcRevenue($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user, $mt);
                $time_entries = CLTimeEntry::getTotalBillableHours($i->format("Y-m"), "month", HelperLibrary::getFirmID(), $user, $mt);
            }
            $row = [
                "name" => $i->format("Y - F"),
                "slug" => $i->format("Y-m"),
                "amount" => $amount,
                "amount_raw" => $amount,
                "time_entries" => $time_entries
            ];
            $row['average_rate'] = $row['time_entries'] == 0 ? 0 : round($row['amount'] / $row['time_entries'], 2);
            $row['amount'] = number_format($row['amount']);
            $row['time_entries'] = number_format($row['time_entries']);
            $data[] = $row;
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
        $data = collect([]);
        if ($integration == "practice_panther") {
            $invT = PPInvoice::where("firm_id", HelperLibrary::getFirmID())
                ->whereRaw("LEFT(issue_date, 7) = '{$month}'")->get();
            $invM = PPInvoice::where("firm_id", HelperLibrary::getFirmID())->has("matter")
                ->whereRaw("LEFT(issue_date, 7) = '{$month}'")->get();
            $invC = $invT->diff($invM);
//            dd($invT, $invM, $invC);
        } elseif ($integration == "clio") {
            $invT = CLInvoice::where("firm_id", HelperLibrary::getFirmID())
                ->whereRaw("LEFT(issued_at, 7) = '{$month}'")
                ->whereNotIn("state", ["deleted", "void"])->get();
            $invM = CLInvoice::where("firm_id", HelperLibrary::getFirmID())->has("matter")
                ->whereRaw("LEFT(issued_at, 7) = '{$month}'")
                ->whereNotIn("state", ["deleted", "void"])->get();
            $invC = $invT->diff($invM);
        }
        $total = 0;
        $name = null;
        foreach ($invM as $invoice) {
            if ($integration == "practice_panther") {
                $amount = $invoice->total;
                $name = $invoice->getMatterName();
            } elseif ($integration == "clio") {
                $amount = $invoice->total;
                $name = $invoice->getMatterDisplayName();
            }
            $row = [
                "contact_name" => $invoice->getContactDisplayName(),
                "name" => $name,
                "amount" => $amount
            ];
//            $row['amount'] = number_format($row['amount']);
            if ($amount > 0) {
                $total += $row['amount'];
                $data->push($row);
            }
        }
        foreach ($invC as $invoice) {
            if ($integration == "practice_panther") {
                // if($invoice->matter !=null){
                //     $amount = PPInvoice::calcRevenue($month, "month", HelperLibrary::getFirmID(), "all", "all", $invoice->matter->first()->id);
                // }
                $amount = $invoice->total;
            } elseif ($integration == "clio") {
                $amount = $invoice->total;
            }
            $row = [
                "contact_name" => $invoice->getContactDisplayName(),
                "name" => "-",
                "amount" => $amount
            ];
            $total += $row['amount'];
            $row['amount'] = number_format($row['amount']);
            if ($amount != 0) {
                $data->push($row);
            }
        }
//        foreach ($contacts as $contact) {
//            $wm += $contact->invoices->sum("total");
//            dd($contact->invoices()->has("matter")->sum("total"), $contact->invoices()->doesntHave("matter")->sum("total"));
//            foreach ($contact->invoices as $inv) {
////                $wm += $inv->has("matter")->sum("total");
////                $wim += $inv->doesntHave("matter")->sum("total");
////                dd($inv->has("matter")->count(), $inv->doesntHave("matter")->count(), $inv->count());
//                foreach ($inv->has("matter")->get() as $invoice) {
//                    if ($integration == "practice_panther") {
//                        $amount = PPInvoice::calcRevenue($month, "month", HelperLibrary::getFirmID(), "all", "all", $invoice->matter->first()->id);
//                    } elseif ($integration == "clio") {
//                        $amount = CLInvoice::calcRevenue($month, "month", HelperLibrary::getFirmID(), "all", "all", $invoice->matter->first()->id);
//                    }
//                    $row = [
//                        "contact_name" => $contact->getDisplayName(),
//                        "name" => $invoice->matter->first()->getName(),
//                        "amount" => $amount
//                    ];
//                    $total += $row['amount'];
//                    $row['amount'] = number_format($row['amount']);
//                    if ($amount != 0) {
//                        $data1->push($row);
//                    }
//                }
//                $data1 = $data1->unique();
////                $data->unique("name");
//                dd($data1->sum("amount"));
//                foreach ($inv->doesntHave("matter")->get() as $invoice) {
//                    if ($integration == "practice_panther") {
//                        $amount = PPInvoice::calcRevenue($month, "month", HelperLibrary::getFirmID(), "all", "all", null, $invoice->clio_contact_id);
//                    } elseif ($integration == "clio") {
//                        $amount = CLInvoice::calcRevenue($month, "month", HelperLibrary::getFirmID(), "all", "all", null, $invoice->clio_contact_id);
//                    }
//                    $row = [
//                        "contact_name" => $contact->getDisplayName(),
//                        "name" => "-",
//                        "amount" => $amount
//                    ];
//                    $total += $row['amount'];
//                    $row['amount'] = number_format($row['amount']);
//                    $data2[] = $row;
//                }
//                $data2 = $data2->unique();
//            }
//        }
//        dd($wm, $wim);
        return (object) ["data" => $data, "total" => $total];
    }
}