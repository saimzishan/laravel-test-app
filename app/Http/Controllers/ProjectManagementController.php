<?php

namespace App\Http\Controllers;

use App\CLTask;
use App\CLUser;
use App\Definition;
use App\Http\Libraries\HelperLibrary;
use App\PPTask;
use App\PPUser;
use Illuminate\Http\Request;

class ProjectManagementController extends Controller
{
    public function tasksPerUsersOpen(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $count1=0;$count2=0;$count3=0;
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
        }  elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
        }
        elseif ($request->scope == 'last-9-months') {
            $financial_year = Definition::getNineMonthsTrail();
        }
        elseif ($request->scope == 'last-6-months') {
            $financial_year = Definition::getHalfYearTrailAverage();
        }
        elseif ($request->scope == 'last-3-months') {
            $financial_year = Definition::getThreeMonthsTrail();
        }else {
            $financial_year = Definition::getFinancialYear();
        }
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated",true)->get();
        } else {
            $users = CLUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated",true)->get();
        }
        foreach ($users as $index=>$user) {
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $labels[] = $user->display_name;
            } else {
                $labels[] = $user->name;
            }
            $colors[] = $this->getRandomColor();
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[$index]["value"] = PPTask::calcTasksPerUser($financial_year, "year", HelperLibrary::getFirmID(), "open", $user->id);
                $data[$index]["total"] = PPTask::calcTasksPerUser($financial_year, "year", HelperLibrary::getFirmID(), "total", $user->id);
                if( $data[$index]["total"] ==0)
                {
                    $data[$index]["percentage"] = 0;
                } else {
                    $data[$index]["percentage"] = round($data[$index]["value"] / $data[$index]["total"]*100);
                }

            } else {
                $data[$index]["value"] = CLTask::calcTasksPerUser($financial_year, "year", HelperLibrary::getFirmID(), "open", $user->id);
                $data[$index]["total"] = CLTask::calcTasksPerUser($financial_year, "year", HelperLibrary::getFirmID(), "total", $user->id);
                if( $data[$index]["total"] ==0)
                {
                    $data[$index]["percentage"] = 0;
                } else {
                    $data[$index]["percentage"] = round($data[$index]["value"] / $data[$index]["total"]*100);
                }
            }
        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            if($data[$i]['value']==0)
            {
                $count1+=1;
            }
            if($data[$i]['total']==0)
            {
                $count2+=1;
            }
            if($data[$i]['percentage']==0)
            {
                $count3+=1;
            }
        }
        if($count1==sizeof($data) and $count2==sizeof($data) and $count3==sizeof($data) )
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
            "colors" => $colors,
            "state" => $request->scope,"display"=>$display
        ]);
    }
    public function tasksPerUsersOverdue(Request $request) {
        $data = [];
        $labels = [];
        $colors = [];
        $count1=0;$count2=0;$count3=0;
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
        }  elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
        }
        elseif ($request->scope == 'last-9-months') {
            $financial_year = Definition::getNineMonthsTrail();
        }
        elseif ($request->scope == 'last-6-months') {
            $financial_year = Definition::getHalfYearTrailAverage();
        }
        elseif ($request->scope == 'last-3-months') {
            $financial_year = Definition::getThreeMonthsTrail();
        }else {
            $financial_year = Definition::getFinancialYear();
        }
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $users = PPUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated",true)->get();
        } else {
            $users = CLUser::where("firm_id", HelperLibrary::getFirmID())->where("can_be_calculated",true)->get();
        }
        foreach ($users as $index=>$user) {
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $labels[] = $user->display_name;
            } else {
                $labels[] = $user->name;
            }
            $colors[] = $this->getRandomColor();
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {
                $data[$index]["value"] = PPTask::calcTasksPerUser($financial_year, "year", HelperLibrary::getFirmID(), "overdue", $user->id);
                $data[$index]["total"] = PPTask::calcTasksPerUser($financial_year, "year", HelperLibrary::getFirmID(), "total", $user->id);
                if( $data[$index]["total"] ==0)
                {
                    $data[$index]["percentage"] = 0;
                } else {
                    $data[$index]["percentage"] = round($data[$index]["value"] / $data[$index]["total"]*100);
                }

            } else {
                $data[$index]["value"] = CLTask::calcTasksPerUser($financial_year, "year", HelperLibrary::getFirmID(), "overdue", $user->id);
                $data[$index]["total"] = CLTask::calcTasksPerUser($financial_year, "year", HelperLibrary::getFirmID(), "total", $user->id);
                if( $data[$index]["total"] ==0)
                {
                    $data[$index]["percentage"] = 0;
                } else {
                    $data[$index]["percentage"] = round($data[$index]["value"] / $data[$index]["total"]*100);
                }
            }
        }
        for($i=0;$i<sizeof($labels);$i++)
        {
            if($data[$i]['value']==0)
            {
                $count1+=1;
            }
            if($data[$i]['total']==0)
            {
                $count2+=1;
            }
            if($data[$i]['percentage']==0)
            {
                $count3+=1;
            }
        }
        if($count1==sizeof($data) and $count2==sizeof($data) and $count3==sizeof($data) )
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
            "colors" => $colors,
            "state" => $request->scope,"display"=>$display
        ]);
    }
}
