<?php

namespace App\Http\Controllers;

use App\Http\Libraries\HelperLibrary;
use App\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function viewSettings(Request $request) {
        if ($request->filled("get") && $request->get("get") == "card-info") {
            $data = HelperLibrary::getSettings(["rag_red", "rag_yellow", "rag_green", "utilization", "realization",
                "collection", "contacts", "matters", "ar_aging", "revenue_expense", "financials",
                "productivity", "matter_tracker", "client_management", "operations_management", "project_management"]);
        } elseif ($request->filled("get") && $request->get("get") == "plans") {
            $data = HelperLibrary::getSettings(["foundation", "foundation_plus", "enhanced", "trial_period"]);
        } else {
            $data = HelperLibrary::getSettings();
        }
        return response()->json($data);
    }
    public function saveSettings(Request $request) {
        $success = false;
        foreach ($request->all() as $k=>$v) {
            $row = Setting::where("key", $k)->first();
            $row->value = $v;
            $success = $row->save();
        }
        return response()->json([
            "success" => $success
        ]);
    }
}
