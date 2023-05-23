<?php

namespace App\Http\Controllers;

use App\CLContact;
use App\CLMatter;
use App\CLPracticeArea;
use App\Definition;
use App\Http\Libraries\HelperLibrary;
use App\PPAccount;
use App\PPContact;
use App\PPMatter;
use App\SummaryAOP;
use Illuminate\Http\Request;

class AOPController extends Controller
{
    public function getAOPMatters() {
        $display=0;
        $data = SummaryAOP::where("firm_id", HelperLibrary::getFirmID())
            ->select(["name", "matters"])
            ->get()
            ->mapToGroups(function($item, $key) {
                return [$item->name=>$item->matters];
            })
            ->map(function($item, $key) {
                return $item[0];
            });
        if(isset($data["Others"]) and sizeof($data)==1)
        {
            if($data["Others"]>0)
            {
                $display=1;
            }else
            {
                $display=0;
            }
        }
        else
        {
            if(sizeof($data)>0)
            {
                $display=1;
            }else
            {
                $display=0;
            }
        }
        return array("data"=>$data,"display"=>$display);
    }
    public function getAOPClients() {
        $display = 0;
        $data = SummaryAOP::where("firm_id", HelperLibrary::getFirmID())
            ->select(["name", "clients"])
            ->get()
            ->mapToGroups(function($item, $key) {
                return [$item->name=>$item->clients];
            })
            ->map(function($item, $key) {
                return $item[0];
            });
        if(isset($data["Others"]) and sizeof($data)==1)
        {
            if($data["Others"]>0)
            {
                $display=1;
            }else
            {
                $display=0;
            }
        }
        else
        {
            if(sizeof($data)>0)
            {
                $display=1;
            }else
            {
                $display=0;
            }
        }
        return array("data"=>$data,"display"=>$display);
    }
}
