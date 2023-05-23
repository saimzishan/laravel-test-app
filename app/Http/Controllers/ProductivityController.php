<?php

namespace App\Http\Controllers;

use App\Exports\FinancialsDescriptiveExport;
use App\Exports\FinancialsDiagnosticExport;
use App\Exports\FinancialsPredictiveExport;
use App\Exports\ProductivityDescriptiveExport;
use App\Exports\ProductivityDiagnosticExport;
use App\Exports\ProductivityPredictiveExport;
use App\Http\Libraries\HelperLibrary;
use App\Http\Libraries\ProductivityLibrary;
use Illuminate\Http\Request;
use Excel;

class ProductivityController extends Controller
{
    public function getDescriptive(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        HelperLibrary::logActivity('User Viewed Productivity Descriptive Page');
        return response()->json([
            "data" => ProductivityLibrary::getDescriptive(HelperLibrary::getFirmIntegration(), $state, $user),
            "filters" => [
                "state" => $state,
                "user" => $user,
            ]
        ]);
    }
    public function getDiagnostic(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        HelperLibrary::logActivity('User Viewed Productivity Diagnostic Page');
        return response()->json([
            "data" => ProductivityLibrary::getDiagnostic(HelperLibrary::getFirmIntegration(), $state, $user),
            "filters" => [
                "state" => $state,
                "user" => $user,
            ]
        ]);
    }
    public function getPredictive(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        HelperLibrary::logActivity('User Viewed Productivity Predictive Page');
        return response()->json([
            "data" => ProductivityLibrary::getPredictive(HelperLibrary::getFirmIntegration(), $state, $user),
            "filters" => [
                "state" => $state,
                "user" => $user,
            ]
        ]);
    }
    /**
     *  Export descriptive data of productivity section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportDescriptive(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        HelperLibrary::logActivity('User Downloaded productivity Descriptive Data');
        $data = collect(ProductivityLibrary::getDescriptive(HelperLibrary::getFirmIntegration(), $state, $user));
        $data = $data->map(function ($item, $key) {
            return collect([
                $item['name'],
                $item['available']."Hrs",
                $item['worked']."Hrs",
                $item['billed']."Hrs",
                $item['collected']."Hrs",
                $item['billed_vs_collected']."%",
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new ProductivityDescriptiveExport($data), time()."-productivity-descriptive.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new ProductivityDescriptiveExport($data), time()."-productivity-descriptive.pdf");
        }
    }
    /**
     *  Export diagnostic data of productivity section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportDiagnostic(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        HelperLibrary::logActivity('User Downloaded productivity Diagnostic Data');
        $data = collect(ProductivityLibrary::getDiagnostic(HelperLibrary::getFirmIntegration(), $state, $user));
        $data = $data->map(function ($item, $key) {
            return collect([
                $item['name'],
                $item['available']."Hrs",
                $item['worked']."Hrs",
                $item['billed']."Hrs",
                $item['target']."Hrs",
                $item['deviation']."Hrs",
                $item['collected']."Hrs",
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new ProductivityDiagnosticExport($data), time()."-productivity-diagnostic.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new ProductivityDiagnosticExport($data), time()."-productivity-diagnostic.pdf");
        }
    }
    /**
     *  Export predictive data of productivity section.
     *
     *  @var Request $request request contains data from request
     *
     * @return \Response
     */
    public function exportPredictive(Request $request) {
        $state = $request->filled("scope") ? $request->scope : "last-12-months";
        $user = $request->filled("user") ? $request->user : "all";
        HelperLibrary::logActivity('User Downloaded productivity Predictive Data');
        $data = collect(ProductivityLibrary::getPredictive(HelperLibrary::getFirmIntegration(), $state, $user));
        $data = $data->map(function ($item, $key) {
            return collect([
                $item['name'],
                $item['available']."Hrs",
                $item['worked']."Hrs",
                $item['billed']."Hrs",
                $item['target']."Hrs",
                $item['deviation_target']."Hrs",
                $item['forecast']."Hrs",
                $item['deviation_forecast']."Hrs",
                $item['collected']."Hrs",
            ]);
        });
        ob_end_clean();
        ob_start();
        if($request->type == "excel") {
            return Excel::download(new ProductivityPredictiveExport($data), time()."-productivity-predictive.xlsx");
        } else if ($request->type == "pdf") {
            return Excel::download(new ProductivityPredictiveExport($data), time()."-productivity-predictive.pdf");
        }
    }
}
