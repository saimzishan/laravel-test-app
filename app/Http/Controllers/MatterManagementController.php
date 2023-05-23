<?php

namespace App\Http\Controllers;

use App\CLContact;
use App\CLMatter;
use App\CLMatterContact;
use App\CLUser;
use App\Definition;
use App\Http\Libraries\HelperLibrary;
use App\PPAccount;
use App\PPMatter;
use App\PPUser;
use Illuminate\Http\Request;

class MatterManagementController extends Controller
{
    public function getTimeKeepers(Request $request) {
        $count=0;
        $fy = Definition::getFinancialYear();
        $firm_id = HelperLibrary::getFirmID();
        $types = [
            "Attorney" => 0,
            "Paralegal Staff" => 0,
            "Admin Staff" => 0,
            "Others" => 0,
        ];
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $matters = PPMatter::where("firm_id", $firm_id)->where("status", "Open")->count();
            $group = PPUser::where("firm_id", $firm_id)->where("can_be_calculated", true)->groupBy("type")->selectRaw("type, count(*) as count")->get();
        } elseif (HelperLibrary::getFirmIntegration() == "clio") {
            $matters = CLMatter::where("firm_id", $firm_id)->where("status", "Open")->count();
            $group = CLUser::where("firm_id", $firm_id)->where("can_be_calculated", true)->groupBy("type")->selectRaw("type, count(*) as count")->get();
        }
        foreach ($types as $k => $v) {
            $get = (clone $group)->where("type", $k);
            if ($get->count() > 0) {
                $types[$k] = round($matters / $get->first()->count, 0);
            }
        }
        if($types["Attorney"]==0 and $types["Paralegal Staff"]==0 and $types["Admin Staff"]==0 and $types["Others"]==0 )
        {
            $display=0;
        } else
        {
            $display=1;
        }
        return response()->json([
            "data" => $types,
            "max" => $matters,
            "display"=>$display
        ]);
    }
    public function getMattersPerUserType(Request $request)
    {
        $display = 0 ;
        $dont_show=0;
        $all = 0;
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $person =[0,0,0,0,0,0,0,0,0,0,0,0];$raw=[0,0,0,0,0,0,0,0,0,0,0,0];$company=[0,0,0,0,0,0,0,0,0,0,0,0];
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $person =[0,0,0,0,0,0,0,0,0,0,0,0];$raw=[0,0,0,0,0,0,0,0,0,0,0,0];$company=[0,0,0,0,0,0,0,0,0,0,0,0];
        }
        elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
            $person =[0,0,0,0,0,0,0,0,0];$raw=[0,0,0,0,0,0,0,0,0];$company=[0,0,0,0,0,0,0,0,0];
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $person =[0,0,0,0,0,0];$raw=[0,0,0,0,0,0];$company=[0,0,0,0,0,0];
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $person =[0,0,0];$raw=[0,0,0];$company=[0,0,0];
        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
            $person =[0,0,0,0,0,0,0,0,0,0,0,0];$raw=[0,0,0,0,0,0,0,0,0,0,0,0];$company=[0,0,0,0,0,0,0,0,0,0,0,0];
        }
        $begin = new \DateTime(substr($year->from, 0, 10));
        $end = new \DateTime(substr($year->to, 0, 10));
        $count = 0;

        if(HelperLibrary::getFirmIntegration()=="practice_panther")
        {
            for($i = $begin; $i <= $end; $i->modify('+1 month'))
            {
                $matter = PPMatter::where("firm_id",HelperLibrary::getFirmID())->where('status',"Open")->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'")->get();
                $all += PPMatter::where("firm_id",HelperLibrary::getFirmID())->where('status',"Open")->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'")->count("id");
                foreach($matter as $m)
                {
                    $d = optional($m->account);
                    $e = optional($d->contacts)->where("is_primary",1);
                        $person[$count]+= optional($e)->count('id');
                        if($d->company_name != null or $d->company_name != "") {
                            $company[$count]+=1;
                        }
                }
                if($person[$count]==0 and $company[$count]==0)
                {
                    $dont_show+=1;
                }
                $count++;
              }
        }
        else
         {
            for($i = $begin; $i <= $end; $i->modify('+1 month'))
            {
                $raw[$count] = CLMatter::where("firm_id",HelperLibrary::getFirmID())->where('status',"Open")->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'")->get();
                $all += CLMatter::where("firm_id",HelperLibrary::getFirmID())->where('status',"Open")->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'")->count("id");
                for($a = 0;$a<sizeof($raw[$count]);$a++)
                {

                    $person[$count]+= $raw[$count][$a]->contacts->where("is_client",1)->where("type","Person")->count("id");
                    $company[$count]+= $raw[$count][$a]->contacts->where("is_client",1)->where("type","Company")->count("id");
                }
                if($person[$count]==0 and $company[$count]==0)
                {
                    $dont_show+=1;
                }
                $count++;
            }
         }
        if($dont_show == sizeof($labels))
        {
            $display=0;
        }
        else
        {
            $display=1;
        }
        return response()->json([
            "all"=>$all,"individual"=>$person,"company"=>$company,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $request->scope,"display"=>$display
        ]);
//                    $person = $raw[$i]->contacts->where("type","Person")->count();
//                    $ccompany = $raw[$i]->contacts->where("type","Company")->count();


    }

}
