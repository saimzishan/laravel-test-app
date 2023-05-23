<?php

namespace App\Http\Libraries;

use App\CLContact;
use App\CLInvoice;
use App\CLInvoiceLineItem;
use App\CLMatter;
use App\CLPracticeArea;
use App\CLTimeEntry;
use App\CLTask;
use App\CLUser;
use App\CLCredit;
use App\Definition;
use App\Firm;
use App\FirmIntegration;
use App\PPAccount;
use App\PPContact;
use App\PPExpense;
use App\PPInvoice;
use App\PPInvoiceLineItem;
use App\PPMatter;
use App\PPTimeEntry;
use App\PPTask;
use App\PPUser;
use App\SummaryMatterTracker;
use Carbon\Carbon;
use App\SummaryWrittenOffByEmployee;
use App\SummaryWrittenOffByClient;

use Illuminate\Support\Facades\Log;

class SummaryLibraryUserProductivity
{
    protected $firm;
    protected $fys;
    protected $lib;

    public function __construct(Firm $firm) {
        $this->firm = $firm;
        $this->fys = [
            "this-year"=>Definition::getFinancialYear("this", $this->firm->id),
            "last-year"=>Definition::getFinancialYear("last", $this->firm->id),
            "last-before-year"=>Definition::getFinancialYear("before-last", $this->firm->id),
        ];
        $this->lib = new DataLibrary($this->firm->id, $this->firm->integration);
    }

    public function run($function="",$user=null) {
        if (empty($function)) {
            $this
                ->userProductivity($user);

        } else {
            $this->$function();
        }
    }

    private function getRow($model, $value, $column="month") {
        $check = $model::where("firm_id", $this->firm->id)
            ->where($column, $value)->first();
        if ($check!= null) {
            return $check;
        } else {
            return (new $model)->fill(["firm_id"=>$this->firm->id, $column=>$value]);
        }
    }
    private function getRowCustom($model, $value, $column="month",$column1,$value1) {
        $check = $model::where("firm_id", $this->firm->id)
            ->where($column, $value)->where($column1, $value1)->first();
        if ($check!= null) {
            return $check;
        } else {
            return (new $model)->fill(["firm_id"=>$this->firm->id, $column=>$value,$column1=>$value1]);
        }
    }

    private function getRowSimple($model) {
        $check = $model::where("firm_id", $this->firm->id)->first();
        if ($check != null) {
            return $check;
        } else {
            return (new $model)->fill(["firm_id"=>$this->firm->id]);
        }
    }


    private function userProductivity($user="none") {

        if($user=="none"){
            if ($this->firm->integration == "practice_panther") {
                $users = PPUser::where('firm_id', $this->firm->id)->where('can_be_calculated', true)->get();
            } else {
                $users = CLUser::where('firm_id', $this->firm->id)->where('can_be_calculated', true)->get();
            }
        }
        else
        {
            if ($this->firm->integration == "practice_panther") {
                $users = PPUser::where('firm_id', $this->firm->id)->where('can_be_calculated', true)->where("id",$user)->get();
            } else {
                $users = CLUser::where('firm_id', $this->firm->id)->where('can_be_calculated', true)->where("id",$user)->get();
            }
        }

        foreach ($this->fys as $key=>$fy) {
            
            if($key != 'last-before-year') {
                $begin = new \DateTime(substr($fy->from, 0, 10));
                $end = new \DateTime(substr($fy->to, 0, 10));
                
                $non_billed_hours = 0;
                for ($i = $begin; $i <= $end; $i->modify('+1 month')) {
                    if ($this->firm->integration == "practice_panther") {
                        foreach ($users as $user) {
                            $ava = PPUser::calcAvailableHour($i->format("Y-m"), "month", $this->firm->id, $user->id);
                            $ava_last = PPUser::calcAvailableHour(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, $user->id);
                            $wor = PPTimeEntry::getTotalBillableHoursUtilization($i->format("Y-m"), "month", $this->firm->id, $user->id);
                            $wor_last = PPTimeEntry::getTotalBillableHoursUtilization(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, $user->id);
                            $col = PPInvoice::calcTotalCollectedHoursAverage($i->format("Y-m"), "month", $this->firm->id, $user->id);
                            $col_last = PPInvoice::calcTotalCollectedHoursAverage(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, $user->id);
                            $billable_hours = PPTimeEntry::getTotalBillableHours($i->format("Y-m"), "month", $this->firm->id, $user->id);
                            $billable_hours_last = PPTimeEntry::getTotalBillableHours(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, $user->id);
                            $billed_hours = PPTimeEntry::getTotalBilledHours($i->format("Y-m"), "month", $this->firm->id, $user->id);
                            $billed_hours_last = PPTimeEntry::getTotalBilledHours(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, $user->id);
                            $open_tasks = PPTask::calcTasksPerUser($i->format("Y-m"), "month", $this->firm->id, "open", $user->id);
                            $overdue_tasks = PPTask::calcTasksPerUser($i->format("Y-m"), "month", $this->firm->id, "overdue", $user->id);
                            $completed_tasks = PPTask::calcTasksPerUser($i->format("Y-m"), "month", $this->firm->id, "completed", $user->id);
                            $total_tasks = PPTask::calcTasksPerUser($i->format("Y-m"), "month", $this->firm->id, "total", $user->id);
                            $monthly_billable_target = PPUser::calcAvailableTimeTarget($i->format("Y-m"), "month", $this->firm->id, $user->id);
                            $non_billed_hours = $billable_hours - $billed_hours;
                            $non_billed_hours_last = $billable_hours_last - $billed_hours_last;
                            $non_billed_hours_last = round($non_billed_hours_last, 1);
                            
                            $this->getRowCustom("\\App\\SummaryUser", $i->format("Y-m-01"), "month", "user", $user->id)
                                ->fill([
                                    "user" => $user->id,
                                    "available_time" => round($ava, 0),
                                    "available_time_mom" => round(($ava_last == 0 ? 0 : ($ava - $ava_last) / $ava_last) * 100, 0),
                                    "worked_time" => round($wor, 0),
                                    "worked_time_mom" => round(($wor_last == 0 ? 0 : ($wor - $wor_last) / $wor_last) * 100, 0),
                                    "collected_time" => round($col, 0),
                                    "collected_time_mom" => round(($col_last == 0 ? 0 : ($col - $col_last) / $col_last) * 100, 0),
                                    "billable_hours" => round($billable_hours, 0),
                                    "billed_hours" => round($billed_hours, 0),
                                    "billed_time" => round($billed_hours, 0),
                                    "non_billed_hours" => round($non_billed_hours, 0),
                                    "billed_hours_mom" => round(($billed_hours_last == 0 ? 0 : ($billed_hours - $billed_hours_last) / $billed_hours_last) * 100, 0),
                                    "non_billed_hours_mom" => round(($non_billed_hours_last == 0 ? 0 : ($non_billed_hours - $non_billed_hours_last) / $non_billed_hours_last) * 100, 0),
                                    "open_tasks" => $open_tasks,
                                    "overdue_tasks" => $overdue_tasks,
                                    "completed_tasks" => $completed_tasks,
                                    "total_tasks" => $total_tasks,
                                    "monthly_billable_target" => $monthly_billable_target,
                                ]) ->save() ;
                                
                        }
                    } else {
                        foreach ($users as $user) {
                            $ava = CLUser::calcAvailableHour($i->format("Y-m"), "month", $this->firm->id, $user->id, $this->firm->getCurrentPackage());
                            $ava_last = CLUser::calcAvailableHour(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, $user->id, $this->firm->getCurrentPackage());
                            $wor = CLTimeEntry::getTotalBillableHoursUtilization($i->format("Y-m"), "month", $this->firm->id, $user->id);
                            $wor_last = CLTimeEntry::getTotalBillableHoursUtilization(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, $user->id);
                            $col = CLInvoice::calcTotalCollectedHoursAverage($i->format("Y-m"), "month", $this->firm->id, $user->id);
                            $col_last = CLInvoice::calcTotalCollectedHoursAverage(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, $user->id);
                            $billable_hours = CLTimeEntry::getTotalBillableHours($i->format("Y-m"), "month", $this->firm->id, $user->id);
                            $billable_hours_last = CLTimeEntry::getTotalBillableHours(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, $user->id);
                            $billed_hours = CLInvoiceLineItem::getTotalBilledHours($i->format("Y-m"), "month", $this->firm->id, $user->id);
                            $billed_hours_last = CLInvoiceLineItem::getTotalBilledHours(date("Y-m", strtotime($i->format("Y-m-01") . ' - 1 month')), "month", $this->firm->id, $user->id);
                            $open_tasks = CLTask::calcTasksPerUser($i->format("Y-m"), "month", $this->firm->id, "open", $user->id);
                            $overdue_tasks = CLTask::calcTasksPerUser($i->format("Y-m"), "month", $this->firm->id, "overdue", $user->id);
                            $completed_tasks = CLTask::calcTasksPerUser($i->format("Y-m"), "month", $this->firm->id, "completed", $user->id);
                            $total_tasks = CLTask::calcTasksPerUser($i->format("Y-m"), "month", $this->firm->id, "total", $user->id);
                            $monthly_billable_target = CLUser::calcAvailableTimeTarget($i->format("Y-m"), "month", $this->firm->id, $user->id);
                            $non_billed_hours = $billable_hours - $billed_hours;
                            $non_billed_hours_last = $billable_hours_last - $billed_hours_last;
                            $non_billed_hours_last = round($non_billed_hours_last, 1);
                            $this->getRowCustom("\\App\\SummaryUser", $i->format("Y-m-01"), "month", "user", $user->id)
                                ->fill([
                                    "user" => $user->id,
                                    "available_time" => round($ava, 0),
                                    "available_time_mom" => round(($ava_last == 0 ? 0 : ($ava - $ava_last) / $ava_last) * 100, 0),
                                    "worked_time" => round($wor, 0),
                                    "worked_time_mom" => round(($wor_last == 0 ? 0 : ($wor - $wor_last) / $wor_last) * 100, 0),
                                    "collected_time" => round($col, 0),
                                    "collected_time_mom" => round(($col_last == 0 ? 0 : ($col - $col_last) / $col_last) * 100, 0),
                                    "billable_hours" => round($billable_hours, 0),
                                    "billed_hours" => round($billed_hours, 0),
                                    "billed_time" => round($billed_hours, 0),
                                    "non_billed_hours" => round($non_billed_hours, 0),
                                    "billed_hours_mom" => round(($billed_hours_last == 0 ? 0 : ($billed_hours - $billed_hours_last) / $billed_hours_last) * 100, 0),
                                    "non_billed_hours_mom" => round(($non_billed_hours_last == 0 ? 0 : ($non_billed_hours - $non_billed_hours_last) / $non_billed_hours_last) * 100, 0),
                                    "open_tasks" => $open_tasks,
                                    "overdue_tasks" => $overdue_tasks,
                                    "completed_tasks" => $completed_tasks,
                                    "total_tasks" => $total_tasks,
                                    "monthly_billable_target" => $monthly_billable_target,
                                ])->save();
                        }
                    }

                }

            }


        }
        FirmIntegration::where('firm_id', HelperLibrary::getFirmID())
            ->update(['status_message' => null,'percentage'=>"100"]);
        return $this;
    }


}