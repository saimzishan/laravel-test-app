<?php

namespace App\Http\Controllers;

use App\CLContact;
use App\CLMatter;
use App\CLMatterContact;
use App\CLUser;
use App\Definition;
use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\SummaryWrittenOffByClientResource;
use App\PPAccount;
use App\PPContact;
use App\PPMatter;
use App\PPUser;
use App\SummaryAOP;
use App\SummaryMonth;
use App\SummaryWrittenOffByClient;
use Illuminate\Http\Request;

class ClientManagementController extends Controller
{
            public function newClientsPerAttorney(Request $request) {
            $data= [];
            $count=0;
            $clients_mom=[];
            $mom_date="";
            $all=[];
            $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
            if ($request->scope == 'last-year') {
                $year = Definition::getFinancialYear("last");
                $labels = Definition::getFinancialYearLabels('last');
                $mom_date =date("Y")-2;
                $mom_date =$mom_date."-12";
            }  elseif ($request->scope == 'last-12-months') {
                $year = Definition::getYearTrail();
                $labels = Definition::getYearTrailLabels();
                $mom_date =  Definition::get_MOM_month("-13 months");
            }
            elseif ($request->scope == 'last-9-months') {
                $year = Definition::getNineMonthsTrail();
                $labels = Definition::getNineMonthsTrailLabels();
            }
            elseif ($request->scope == 'last-6-months') {
                $year = Definition::getHalfYearTrailAverage();
                $labels = Definition::getHalfYearTrailAverageLabels();
                $mom_date =  Definition::get_MOM_month("-7 months");

            }
            elseif ($request->scope == 'last-3-months') {
                $year = Definition::getThreeMonthsTrail();
                $labels = Definition::getThreeMonthsTrailLabels();
                $mom_date =  Definition::get_MOM_month("-4 months");
            }
            else {
                $year = Definition::getFinancialYear();
                $labels = Definition::getFinancialYearLabels();
                $mom_date =date("Y")-1;
                $mom_date =$mom_date."-12";
            }
            if (HelperLibrary::getFirmIntegration() == "practice_panther") {

//            code here for Practice Panther :P
            }
            else
            {
                if($mt=="all")
                {
                    $all = CLUser::select('id')->where("firm_id",HelperLibrary::getFirmID())->where('type','Attorney')->get();
                    for($i=0;$i<sizeof($all);$i++)
                    {
                        $all[$i]=$all[$i]->id;
                    }
                    $matterID=[];
                    $clientID=[];
                    $mom = [];
                    $matterID_AgainstAttorney=[];
                    $clientID_AgainstAttorney=[];
                    $data_raw = CLMatterContact::select('clio_matter_id','clio_contact_id')->where('firm_id',HelperLibrary::getFirmID())->distinct()->get();
                    for($i=0;$i<sizeof($data_raw);$i++)
                    {
                        $matterID[$i]=$data_raw[$i]->clio_matter_id;
                    }
                    for($i=0;$i<sizeof($data_raw);$i++)
                    {
                        $clientID[$i] = CLMatterContact::select('clio_contact_id')->where("clio_matter_id",$data_raw[$i]->clio_matter_id)->where("firm_id",HelperLibrary::getFirmID())
                            ->distinct()->get();
                        $clientID[$i] = $clientID[$i][0]->clio_contact_id;
                    }
                    for($i=0;$i<sizeof($matterID);$i++)
                    {
                        $matterID_AgainstAttorney[$i]=CLMatter::select('id')->where("firm_id",HelperLibrary::getFirmID())->where('id',$matterID[$i])
                            ->whereIn('clio_originating_attorney_id',$all)->distinct()->get();
                        if($matterID_AgainstAttorney[$i] !="[]")
                        {
                            $matterID_AgainstAttorney[$i] = $matterID_AgainstAttorney[$i][0]->id;
                        }
                        else
                        {
                            $matterID_AgainstAttorney[$i] = 0;
                        }
                    }
                    for($i=0;$i<sizeof($matterID_AgainstAttorney);$i++)
                    {
                        $clientID_AgainstAttorney[$i] = CLMatterContact::select('clio_contact_id')->where("firm_id",HelperLibrary::getFirmID())
                            ->where('clio_matter_id',$matterID_AgainstAttorney[$i])
                            ->distinct()->get();
                        if($clientID_AgainstAttorney[$i]!="[]")
                        {
                            $clientID_AgainstAttorney[$i] = $clientID_AgainstAttorney[$i][0]->clio_contact_id;
                        }
                        else
                        {
                            $clientID_AgainstAttorney[$i] = 0 ;
                        }
                    }
                    for($i=0;$i<sizeof($labels);$i++) {
                        $data[$i] = CLContact::select('id')->whereIn("id", $clientID_AgainstAttorney)
                            ->where('created_at', "Like", HelperLibrary::getMonthsFromRange($year, true)[$i]."%")
                            ->count();

                    }

                    //calculating mom :P :D
                    $a=sizeof($data)-1;
                    for($i=sizeof($data)-1;$i>=0;$i--)
                    {
                        if($i==0)
                        {
                            $current=$data[0];
                            $last=CLContact::select('id')->whereIn("id", $clientID_AgainstAttorney)
                                ->where('created_at', "Like", $mom_date."%")->where('firm_id',HelperLibrary::getFirmID())
                                ->count();
//                            return $last;
                            if($last!=0)
                            {
                                $clients_mom[$i]=round((($current-$last)/$last)*100,0);
                            }
                            else
                            {
                                $clients_mom[$i]=0;
                            }
                        }
                        else
                        {
                            $a=$a-1;
                            $current=$data[$i];
                            $last=$data[$a];
                            if($last!=0)
                            {
                                $clients_mom[$i]=round((($current-$last)/$last)*100,0);
                            }
                            else
                            {
                                $clients_mom[$i]=0;
                            }

                        }

                    }
                    for($i=0;$i<sizeof($clients_mom);$i++)
                    {
                        $mom[$i]=$clients_mom[$i];
                    }
                    for($i=0;$i<sizeof($labels);$i++)
                    {
                        if($data[$i]==0 and $mom[$i]==0)
                        {
                            $count+=1;
                        }
                    }
                   if(sizeof($data)==$count)
                   {
                       $display=0;
                   }
                   else{
                       $display=1;
                   }


                    return response()->json([
                        "data" => $data,
                        "mom"=>$mom,
                        "labels" => $labels,
                        "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                        "state" => $request->scope,
                        "mt"=>$mt,"display"=>$display
                    ]);
                }
                else
                {
                    $matterID=[];
                    $clientID=[];
                    $mom = [];
                    $matterID_AgainstAttorney=[];
                    $clientID_AgainstAttorney=[];
                    $data_raw = CLMatterContact::select('clio_matter_id','clio_contact_id')->where('firm_id',HelperLibrary::getFirmID())->distinct()->get();
                    for($i=0;$i<sizeof($data_raw);$i++)
                    {
                        $matterID[$i]=$data_raw[$i]->clio_matter_id;
                    }
                    for($i=0;$i<sizeof($data_raw);$i++)
                    {
                        $clientID[$i] = CLMatterContact::select('clio_contact_id')->where("clio_matter_id",$data_raw[$i]->clio_matter_id)->where("firm_id",HelperLibrary::getFirmID())
                        ->distinct()->get();
                        $clientID[$i] = $clientID[$i][0]->clio_contact_id;
                    }
                    for($i=0;$i<sizeof($matterID);$i++)
                    {
                        $matterID_AgainstAttorney[$i]=CLMatter::select('id')->where("firm_id",HelperLibrary::getFirmID())->where('id',$matterID[$i])
                            ->where('clio_originating_attorney_id',$mt)->distinct()->get();
                            if($matterID_AgainstAttorney[$i] !="[]")
                            {
                                $matterID_AgainstAttorney[$i] = $matterID_AgainstAttorney[$i][0]->id;
                            }
                            else
                            {
                                $matterID_AgainstAttorney[$i] = 0;
                            }
                    }
                    for($i=0;$i<sizeof($matterID_AgainstAttorney);$i++)
                    {
                        $clientID_AgainstAttorney[$i] = CLMatterContact::select('clio_contact_id')->where("firm_id",HelperLibrary::getFirmID())
                            ->where('clio_matter_id',$matterID_AgainstAttorney[$i])
                            ->distinct()->get();
                        if($clientID_AgainstAttorney[$i]!="[]")
                        {
                            $clientID_AgainstAttorney[$i] = $clientID_AgainstAttorney[$i][0]->clio_contact_id;
                        }
                        else
                        {
                            $clientID_AgainstAttorney[$i] = 0 ;
                        }
                    }
                    for($i=0;$i<sizeof($labels);$i++) {
                        $data[$i] = CLContact::select('id')->whereIn("id", $clientID_AgainstAttorney)
                            ->where('created_at', "Like", HelperLibrary::getMonthsFromRange($year, true)[$i]."%")
                            ->count();

                        }
                    //calculating mom :P :D
                    $a=sizeof($data)-1;
                    for($i=sizeof($data)-1;$i>=0;$i--)
                    {
                        if($i==0)
                        {
                            $current=$data[0];
                            $last=CLContact::select('id')->whereIn("id", $clientID_AgainstAttorney)
                                ->where('created_at', "Like", $mom_date."%")->where('firm_id',HelperLibrary::getFirmID())
                                ->count();
//                            return $last;
                            if($last!=0)
                            {
                                $clients_mom[$i]=round((($current-$last)/$last)*100,0);
                            }
                            else
                            {
                                $clients_mom[$i]=0;
                            }
                        }
                        else
                        {
                            $a=$a-1;
                            $current=$data[$i];
                            $last=$data[$a];
                            if($last!=0)
                            {
                                $clients_mom[$i]=round((($current-$last)/$last)*100,0);
                            }
                            else
                            {
                                $clients_mom[$i]=0;
                            }

                        }

                    }
                    for($i=0;$i<sizeof($clients_mom);$i++)
                    {
                        $mom[$i]=$clients_mom[$i];
                    }
                    for($i=0;$i<sizeof($labels);$i++)
                    {
                        if($data[$i]==0 and $mom[$i]==0)
                        {
                            $count+=1;
                        }
                    }
                    if(sizeof($data)==$count)
                    {
                        $display=0;
                    }
                    else{
                        $display=1;
                    }

//                    return $mom;

                    return response()->json([
                        "data" => $data,
                        "mom"=>$mom,
                        "labels" => $labels,
                        "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                        "state" => $request->scope,
                        "mt"=>$mt,"display"=>$display
                    ]);
                }


            }
        }

    public function newMattersPerAttorney(Request $request) {
        $data= [];
        $matters_mom=[];
        $mom=[];
        $mom_date="";
        $all=[];
        $count=0;
        $display=0;
        $mt = $request->filled("matter-type") ? $request->get("matter-type") : "all";
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $mom_date =date("Y")-2;
            $mom_date =$mom_date."-12";
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $mom_date = Definition::get_MOM_month('-13 months');
        }
        elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
            $mom_date = Definition::get_MOM_month('-10 months');
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $mom_date = Definition::get_MOM_month('-7 months');
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $mom_date = Definition::get_MOM_month('-4 months');
        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
            $mom_date =date("Y")-1;
            $mom_date =$mom_date."-12";
        }

        if (HelperLibrary::getFirmIntegration() == "practice_panther") {

//            code here for Practice Panther :P
        }
        else
        {
            if($mt=="all")
            {
//                $data_raw = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())
//                    ->select(["new_matters","matters_mom"])
//                    ->whereIn("month", HelperLibrary::getMonthsFromRange($year, true))
//                    ->orderby("month", "asc")->get();
//                foreach ($data_raw as $k=>$v) {
//                    $data[] = $v['new_matters'];
//                    $mom[]=$v['matters_mom'];
//                }
                $all = CLUser::select('id')->where("firm_id",HelperLibrary::getFirmID())->where('type','Attorney')->get();
                for($i=0;$i<sizeof($all);$i++)
                {
                    $all[$i]=$all[$i]->id;
                }
                for($i=0;$i<sizeof($labels);$i++)
                {
                    $data[$i]= CLMatter::select('id')
                        ->where('firm_id',HelperLibrary::getFirmID())
                        ->whereIn('clio_originating_attorney_id',$all)->where('open_date',"Like",HelperLibrary::getMonthsFromRange($year,true)[$i]."%")
                        ->count();
                }
//                      MOM
                $a=sizeof($data)-1;
                for($i=sizeof($data)-1;$i>=0;$i--)
                {
                    if($i==0)
                    {
                        $current=$data[0];
                        $last= CLMatter::select('id')
                            ->where('firm_id',HelperLibrary::getFirmID())
                            ->whereIn('clio_originating_attorney_id',$all)->where('open_date',"Like",$mom_date."%")
                            ->count();
//                            return $last;
                        if($last!=0)
                        {
                            $matters_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $matters_mom[$i]=0;
                        }
                    }
                    else
                    {
                        $a=$a-1;
                        $current=$data[$i];
                        $last=$data[$a];
                        if($last!=0)
                        {
                            $matters_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $matters_mom[$i]=0;
                        }

                    }

                }
                for($i=0;$i<sizeof($matters_mom);$i++)
                {
                    $mom[$i]=$matters_mom[$i];
                }
                for($i=0;$i<sizeof($labels);$i++)
                {
                    if($data[$i]==0 and $mom[$i]==0)
                    {
                        $count+=1;
                    }
                }
                if(sizeof($data)==$count)
                {
                    $display=0;
                }
                else{
                    $display=1;
                }

                return response()->json([
                    "data" => $data,
                    "mom"=>$mom,
                    "labels" => $labels,
                    "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                    "state" => $request->scope,
                    "mt" => $mt,"display"=>$display
                ]);
            }
            else
            {
//                 $attorneyID =  CLUser::select('id')->where('firm_id',HelperLibrary::getFirmID())->where('name',$mt)->get();
                 for($i=0;$i<sizeof($labels);$i++)
                {
                    $data[$i]= CLMatter::select('id')
                     ->where('firm_id',HelperLibrary::getFirmID())
                     ->where('clio_originating_attorney_id',$mt)->where('open_date',"Like",HelperLibrary::getMonthsFromRange($year,true)[$i]."%")
                     ->count();
                }
                 // Mom
                $a=sizeof($data)-1;
                for($i=sizeof($data)-1;$i>=0;$i--)
                {
                    if($i==0)
                    {
                        $current=$data[0];
                        $last= CLMatter::select('id')
                            ->where('firm_id',HelperLibrary::getFirmID())
                            ->where('clio_originating_attorney_id',$mt)->where('open_date',"Like",$mom_date."%")
                            ->count();
//                            return $last;
                        if($last!=0)
                        {
                            $matters_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $matters_mom[$i]=0;
                        }
                    }
                    else
                    {
                        $a=$a-1;
                        $current=$data[$i];
                        $last=$data[$a];
                        if($last!=0)
                        {
                            $matters_mom[$i]=round((($current-$last)/$last)*100,0);
                        }
                        else
                        {
                            $matters_mom[$i]=0;
                        }

                    }

                }
                for($i=0;$i<sizeof($matters_mom);$i++)
                {
                    $mom[$i]=$matters_mom[$i];
                }
                for($i=0;$i<sizeof($labels);$i++)
                {
                    if($data[$i]==0 and $mom[$i]==0)
                    {
                        $count+=1;
                    }
                }
                if(sizeof($data)==$count)
                {
                    $display=0;
                }
                else{
                    $display=1;
                }
                return response()->json([
                    "data" => $data,
                    "mom"=>$mom,
                    "labels" => $labels,
                    "scope" => ucwords(str_replace('-', ' ', $request->scope)),
                    "state" => $request->scope,
                    "mt" => $mt,"display"=>$display
                ]);
            }


        }

    }
    public function newClientsPerAOP(Request $request) {
        $display = 0 ;
        if ($request->scope == 'today') {
            $monthYear = date("Y-m-d");
            $type = "today";
        } elseif ($request->scope == 'this-month') {
            $monthYear = date("Y-m");
            $type = "month";
        } elseif ($request->scope == 'last-month') {
            $monthYear = date("Y-m", strtotime("-1 month"));
            $type = "month";
        } elseif ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
            $type = "year";
        }  elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
            $type = "year";
        } elseif ($request->scope == 'this-year') {
            $financial_year = Definition::getFinancialYear();
            $type = "year";
        }
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            if ($type == "year") {
                $data = PPMatter::calcNewClientsPerAOPYearWise($financial_year, HelperLibrary::getFirmID());
            } else {
                $data = PPMatter::calcNewClientsPerAOP($monthYear, $type, HelperLibrary::getFirmID());
            }
        } else {
            if ($type == "year") {
                $data = CLMatter::calcNewClientsPerAOPYearWise($financial_year, HelperLibrary::getFirmID());
            } else {
                $data = CLMatter::calcNewClientsPerAOP($monthYear, $type, HelperLibrary::getFirmID());
            }
//
        }
        return response()->json([
            "data" => array_values($data),
            "labels" => array_keys($data),
            "colors" => $this->getRandomColor(count($data)),
            "state" => $request->scope,
        ]);
    }
    public function newMattersPerAOP(Request $request) {
        if ($request->scope == 'today') {
            $monthYear = date("Y-m-d");
            $type = "today";
        } elseif ($request->scope == 'this-month') {
            $monthYear = date("Y-m");
            $type = "month";
        } elseif ($request->scope == 'last-month') {
            $monthYear = date("Y-m", strtotime("-1 month"));
            $type = "month";
        } elseif ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
            $type = "year";
        }  elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
            $type = "year";
        } elseif ($request->scope == 'this-year') {
            $financial_year = Definition::getFinancialYear();
            $type = "year";
        }
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            if ($type == "year") {
                $data = PPMatter::calcNewMattersPerAOPYearWise($financial_year, HelperLibrary::getFirmID());
            } else {
                $data = PPMatter::calcNewMattersPerAOP($monthYear, $type, HelperLibrary::getFirmID())->toArray();
            }
        } else {
            if ($type == "year") {
                $data = CLMatter::calcNewMattersPerAOPYearWise($financial_year, HelperLibrary::getFirmID());
            } else {
                $data = CLMatter::calcNewMattersPerAOP($monthYear, $type, HelperLibrary::getFirmID())->toArray();
            }
        }
        return response()->json([
            "data" => array_values($data),
            "labels" => array_keys($data),
            "colors" => $this->getRandomColor(count($data)),
            "state" => $request->scope,
        ]);
    }
    public function top5AOPByRevenue(Request $request) {
        $count1=0;$data=[];$labels=[];
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
        }  elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
        } else {
            $financial_year = Definition::getFinancialYear();
        }
        $data_raw = SummaryAOP::where("firm_id", HelperLibrary::getFirmID())
            ->select(["name", "revenue","gross_profit_margin"])->orderBy("revenue", "desc")->limit(10)->get();
        foreach ($data_raw as $k=>$v) {
            $data["revenue"][] = round($v->revenue/1000,1);
            $data["profit"][] = round($v->gross_profit_margin/1000,1);
            $labels[]=$v->name;
        }
        for($i=0;$i<sizeof($data);$i++)
        {
            if(array_values($data)[$i]==0)
            {
                $count1+=1;
            }
        }
        if($count1==sizeof($data))
        {
            $display=0;
        }
        else{
            $display=1;
        }
        return response()->json([
            "data" => $data,
            "labels" => $labels,
            "state" => $request->scope,"display"=>$display
        ]);
    }
    public function top5AOPByOutstandingDues(Request $request) {
        $count1=0;$data=[];
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
        }  elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
        } else {
            $financial_year = Definition::getFinancialYear();
        }
        $data_raw = SummaryAOP::where("firm_id", HelperLibrary::getFirmID())
            ->select(["name", "outstanding_dues"])->orderBy("outstanding_dues", "desc")->limit(10)->get();
        foreach ($data_raw as $k=>$v) {
            $data[$v->name] = round($v->outstanding_dues/1000,1);
        }
        for($i=0;$i<sizeof($data);$i++)
        {
            if(array_values($data)[$i]==0)
            {
                $count1+=1;
            }
        }
        if($count1==sizeof($data))
        {
            $display=0;
        }
        else{
            $display=1;
        }
        return response()->json([
            "data" => array_values($data),
            "labels" => array_keys($data),
            "state" => $request->scope,"display"=>$display
        ]);
    }
    public function top5AOPByGPM(Request $request) {
        $count1=0;$data=[];
        if ($request->scope == 'last-year') {
            $financial_year = Definition::getFinancialYear("last");
        }  elseif ($request->scope == 'last-12-months') {
            $financial_year = Definition::getYearTrail();
        } else {
            $financial_year = Definition::getFinancialYear();
        }
        $data_raw = SummaryAOP::where("firm_id", HelperLibrary::getFirmID())
            ->select(["name", "gross_profit_margin"])->orderBy("gross_profit_margin", "desc")->limit(10)->get();
        foreach ($data_raw as $k=>$v) {
            $data[$v->name] = round($v->gross_profit_margin/1000,1);
        }
        for($i=0;$i<sizeof($data);$i++)
        {
            if(array_values($data)[$i]==0)
            {
                $count1+=1;
            }
        }
        if($count1==sizeof($data))
        {
            $display=0;
        }
        else{
            $display=1;
        }
        return response()->json([
            "data" => array_values($data),
            "labels" => array_keys($data),
            "state" => $request->scope,"display"=>$display
        ]);
    }
    public function getTimeKeepers(Request $request) {
        $fy = Definition::getFinancialYear();
        $firm_id = HelperLibrary::getFirmID();
        $types = [
            "Owner (Attorney)" => 0,
            "Sr. Associate" => 0,
            "Jr. Associate" => 0,
            "Paralegal Staff" => 0,
            "Admin Staff" => 0,
            "Others" => 0,
        ];
        if (HelperLibrary::getFirmIntegration() == "practice_panther") {
            $clients = PPContact::calcPrimaryClients($fy, "year", $firm_id);
            $group = PPUser::where("firm_id", $firm_id)->where("can_be_calculated", true)->groupBy("type")->selectRaw("type, count(*) as count")->get();
        } elseif (HelperLibrary::getFirmIntegration() == "clio") {
            $clients = CLContact::calcPrimaryClients($fy, "year", $firm_id);
            $group = CLUser::where("firm_id", $firm_id)->where("can_be_calculated", true)->groupBy("type")->selectRaw("type, count(*) as count")->get();
        }
        foreach ($types as $k => $v) {
            $get = (clone $group)->where("type", $k);
            if ($get->count() > 0) {
                $types[$k] = round($clients / $get->first()->count, 0);
            }
        }
        return response()->json([
            "data" => $types,
            "max" => $clients,
        ]);
    }
    //written off report
    public function getClientWrittenoff() {
        $data = SummaryWrittenOffByClient::where("firm_id",HelperLibrary::getFirmID())->paginate(HelperLibrary::perPage());
        $data = SummaryWrittenOffByClientResource::collection($data);
        return $data;

    }
    public function getClientsTypePerMonth(Request $request)
    {
        $display = 0 ;
        $scope = 'this-year';
        $dont_show = 0;
        if ($request->scope == 'last-year') {
            $year = Definition::getFinancialYear("last");
            $labels = Definition::getFinancialYearLabels('last');
            $person =[0,0,0,0,0,0,0,0,0,0,0,0];$raw=[0,0,0,0,0,0,0,0,0,0,0,0];$company=[0,0,0,0,0,0,0,0,0,0,0,0];
            $scope=$request->scope;
        }  elseif ($request->scope == 'last-12-months') {
            $year = Definition::getYearTrail();
            $labels = Definition::getYearTrailLabels();
            $person =[0,0,0,0,0,0,0,0,0,0,0,0];$raw=[0,0,0,0,0,0,0,0,0,0,0,0];$company=[0,0,0,0,0,0,0,0,0,0,0,0];
            $scope=$request->scope;
        }
        elseif ($request->scope == 'last-9-months') {
            $year = Definition::getNineMonthsTrail();
            $labels = Definition::getNineMonthsTrailLabels();
            $person =[0,0,0,0,0,0,0,0,0];$raw=[0,0,0,0,0,0,0,0,0];$company=[0,0,0,0,0,0,0,0,0];
            $scope=$request->scope;
        }
        elseif ($request->scope == 'last-6-months') {
            $year = Definition::getHalfYearTrailAverage();
            $labels = Definition::getHalfYearTrailAverageLabels();
            $person =[0,0,0,0,0,0];$raw=[0,0,0,0,0,0];$company=[0,0,0,0,0,0];
            $scope=$request->scope;
        }
        elseif ($request->scope == 'last-3-months') {
            $year = Definition::getThreeMonthsTrail();
            $labels = Definition::getThreeMonthsTrailLabels();
            $person =[0,0,0];$raw=[0,0,0];$company=[0,0,0];
            $scope=$request->scope;
        }
        else {
            $year = Definition::getFinancialYear();
            $labels = Definition::getFinancialYearLabels();
            $mom_date =date("Y")-1;
            $person =[0,0,0,0,0,0,0,0,0,0,0,0];$raw=[0,0,0,0,0,0,0,0,0,0,0,0];$company=[0,0,0,0,0,0,0,0,0,0,0,0];

        }
        $all=0;
        $begin = new \DateTime(substr($year->from, 0, 10));
        $end = new \DateTime(substr($year->to, 0, 10));
        $count = 0;
        if(HelperLibrary::getFirmIntegration()=='practice_panther')
        {
            for($i = $begin; $i <= $end; $i->modify('+1 month'))
            {
                $all+= PPAccount::where("firm_id",HelperLibrary::getFirmID())->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'")->count("id");
                $person[$count] = PPContact::where("firm_id",HelperLibrary::getFirmID())->where("is_primary",1)
                    ->whereHas("account", function($q) use ($i){
                        $q->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'");
                        $q->where('company_name','');
                    })->count("id");
                $company[$count] = PPAccount::where("firm_id",HelperLibrary::getFirmID())->where('company_name',"<>",'')->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'")->count("id");
                if($person[$count]==0 and $company[$count]==0)
                {
                    $dont_show+=1;
                }
                $count++;

            }
            if($dont_show == sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }
        }
        else
        {
            for($i = $begin; $i <= $end; $i->modify('+1 month'))
            {
                $all+= CLContact::where("firm_id",HelperLibrary::getFirmID())->where('is_client',1)->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'")->count("id");
                $person[$count] = CLContact::where("firm_id",HelperLibrary::getFirmID())->where('is_client',1)->where("type","Person")->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'")->count("id");
                $company[$count] = CLContact::where("firm_id",HelperLibrary::getFirmID())->where('is_client',1)->where("type","Company")->whereRaw("created_at >= '{$i->format("Y-m-d")}' and created_at <= '{$i->format("Y-m-t")}'")->count("id");
                if($person[$count]==0 and $company[$count]==0)
                {
                    $dont_show+=1;
                }
                $count++;

            }
            if($dont_show == sizeof($labels))
            {
                $display=0;
            }
            else
            {
                $display=1;
            }


        }

        return response()->json([
            "all"=>$all,"individual"=>$person,"company"=>$company,
            "labels" => $labels,
            "scope" => ucwords(str_replace('-', ' ', $request->scope)),
            "state" => $scope,"display"=>$display
        ]);
    }
}
