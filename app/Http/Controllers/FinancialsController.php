<?php

namespace App\Http\Controllers;

use App\Exports\FinancialsDescriptiveExport;
use App\Exports\FinancialsDiagnosticExport;
use App\Exports\FinancialsPredictiveExport;
use App\Http\Libraries\FinancialsLibrary;
use App\Http\Libraries\HelperLibrary;
use Illuminate\Http\Request;
use Excel;

class FinancialsController extends Controller
{
    public function getDescriptive(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        HelperLibrary::logActivity('User Viewed Financials Descriptive Page');
        return response()->json([
            "data" => FinancialsLibrary::getDescriptive(HelperLibrary::getFirmIntegration(), $state, $user),
            "filters" => [
                "state" => $state,
                "user" => $user,
            ]
        ]);
    }
    public function getDiagnostic(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        HelperLibrary::logActivity('User Viewed Financials Diagnostic Page');
        return response()->json([
            "data" => FinancialsLibrary::getDiagnostic(HelperLibrary::getFirmIntegration(), $state, $user),
            "filters" => [
                "state" => $state,
                "user" => $user,
            ]
        ]);
    }
    public function getPredictive(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        HelperLibrary::logActivity('User Viewed Financials Predictive Page');
        return response()->json([
            "data" => FinancialsLibrary::getPredictive(HelperLibrary::getFirmIntegration(), $state, $user),
            "filters" => [
                "state" => $state,
                "user" => $user,
            ]
        ]);
    }
    /**
     *  Export descriptive data of financials section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportDescriptive(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        $type = $request->filled("user") ? $request->type : "";
        HelperLibrary::logActivity('User Downloaded Financials Descriptive Data');
        $data = collect(FinancialsLibrary::getDescriptive(HelperLibrary::getFirmIntegration(), $state, $user));
        $data = $data->map(function ($item, $key) {
            return collect([
                $item['name'],
                "$".number_format($item['revenue'],2),
                "$".number_format($item['average_rate'],2),
                $item['percentage_growth_revenue']."%",
                "$".number_format($item['expense'],2),
                $item['percentage_growth_expense']."%",
                "$".number_format($item['collection'],2),
                $item['percentage_growth_collection']."%",
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new FinancialsDescriptiveExport($data), time()."-financials-descriptive.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new FinancialsDescriptiveExport($data), time()."-financials-descriptive.pdf");
        }


    }
    /**
     *  Export diagnostic data of financials section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportDiagnostic(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        HelperLibrary::logActivity('User Downloaded Financials Diagnostic Data');
        $data = collect(FinancialsLibrary::getDiagnostic(HelperLibrary::getFirmIntegration(), $state, $user));
        $data = $data->map(function ($item, $key) {
            return collect([
                $item['name'],
                "$".number_format($item['revenue'],2),
                "$".number_format($item['target'],2),
                "$".number_format($item['actual_vs_target'],2),
                "$".number_format($item['average_rate'],2),
                "$".number_format($item['average_rate_target'],2),
                "$".number_format($item['actual_vs_target_avgr'],2),
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new FinancialsDiagnosticExport($data), time()."-financials-diagnostic.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new FinancialsDiagnosticExport($data), time()."-financials-diagnostic.pdf");
        }
    }
    /**
     *  Export predictive data of financials section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportPredictive(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        HelperLibrary::logActivity('User Downloaded Financials Predictive Data');
        $data = collect(FinancialsLibrary::getPredictive(HelperLibrary::getFirmIntegration(), $state, $user));
        $data = $data->map(function ($item, $key) {
            return collect([
                $item['name'],
                "$".number_format($item['revenue'],2),
                "$".number_format($item['forecast'],2),
                "$".number_format($item['actual_vs_forecast'],2),
                "$".number_format($item['average_rate'],2),
                "$".number_format($item['average_rate_forecast'],2),
                "$".number_format($item['actual_vs_forecast_avgr'],2),
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new FinancialsPredictiveExport($data), time()."-financials-predictive.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new FinancialsPredictiveExport($data), time()."-financials-predictive.pdf");
        }
    }
}
