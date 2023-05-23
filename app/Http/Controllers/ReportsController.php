<?php

namespace App\Http\Controllers;

use App\Definition;
use App\Http\Libraries\HelperLibrary;
use App\SummaryAllTime;
use App\SummaryAOP;
use App\SummaryClient;
use App\SummaryMatterTracker;
use App\SummaryMonth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use niklasravnsborg\LaravelPdf\PdfWrapper;
use App\PPInvoice;
use App\CLInvoice;
use App\FirmIntegration;

class ReportsController extends Controller
{
    public function generateMonthlyReports(Request $request)
    {
        $month = $this->checkMonth($request->month);
        $status = FirmIntegration::select('status')->where('firm_id',HelperLibrary::getFirmID())->get();
        if(HelperLibrary::getFirmID() && $status[0]->status=="Synced" )
        {
            $firm = HelperLibrary::getFirm()->name;
            $Productivity = $this->ReportGenerator('productivity', date('Y'));
            $Financial = $this->ReportGenerator('financial', date('Y'));
            $Green = $this->ReportGenerator('MatterTracker', '', "green");
            $Red = $this->ReportGenerator('MatterTracker', '', 'red');
            $Yellow = $this->ReportGenerator('MatterTracker', "", 'yellow');
            $TotalMatters = $this->getTotalMatters();
            $AR = $this->ArAging($month);
            $ThisMonthNewClients = $this->ReportGenerator('ThisMonthNewClients',$month);
            $ThisMonthClientsMom = $this->ReportGenerator('ThisMonthClientsMom',$month);
            $LastMonthClientsMom = $this->ReportGenerator('LastMonthClientsMom');
            $ThisMonthRevenue = $this->ReportGenerator('ThisMonthRevenue',$month);
            $ThisMonthRevenueMom = $this->ReportGenerator('ThisMonthRevenueMom',$month);
            $LastMonthRevenueMom = $this->ReportGenerator('LastMonthRevenueMom',$month);
            $_2LastMonthRevenueMom = $this->ReportGenerator('2LastMonthRevenueMom',$month);
            $ThisMonthCollectionTrend = $this->ReportGenerator('ThisMonthCollectionTrend',$month);
            $ThisMonthCollectionMom = $this->ReportGenerator('ThisMonthCollectionMom',$month);
            $LastMonthCollectionMom = $this->ReportGenerator('LastMonthCollectionMom',$month);
            $_2LastMonthCollectionMom = $this->ReportGenerator('2LastMonthCollectionMom');
            $NewClients = $this->ReportGenerator('NewClients',$month);
            $ClientsMom = $this->ReportGenerator('ClientsMom',$month);
            $Realization = $this->ReportGenerator('Realization');
            $Utilization = $this->ReportGenerator('Utilization');
            $CollectionRate = $this->ReportGenerator('Collection');
            $Top10ClientsByRevenue = $this->ReportGenerator('Top10ClientsByRevenue',$month);
            $Top10ClientsByOutStanding = $this->ReportGenerator('Top10ClientsByOutStanding',$month);
            $AOP = $this->ReportGenerator('AOP');
            $FinancialTrial = $this->ReportGenerator('FinancialTrial',$month);
            $ProductivityTrial = $this->ReportGenerator('ProductivityTrial',$month);
            $ExpanseTrend = $this->ReportGenerator('ExpenseTrend');
            $ExpenseMom = $this->ReportGenerator('ExpenseMom');
            $RevenueTrend = $this->ReportGenerator('RevenueTrend',$month);
            $RevenueMom = $this->ReportGenerator('RevenueMom',$month);
            $CollectionTrend = $this->ReportGenerator('CollectionTrend',$month);
            $CollectionMom = $this->ReportGenerator('CollectionMom',$month);
            $NewMatters = $this->ReportGenerator('NewMatters',$month);
            $NewMattersMom=$this->ReportGenerator('NewMattersMom',$month);
            $NewMattersHalfTrialAverage = $this->AverageCalculator('NewMatters', "6 months", $month);
            $NewMattersTrialAverage = $this->AverageCalculator('NewMatters', "12 months", $month);
            $ThisMonthNewMatters = $this->ReportGenerator('ThisMonthNewMatters',$month);
            $ClientsMomHalfTrialAverage = $this->AverageCalculator('ClientsMom', '6 months', $month);
            $ClientsMomTrialAverage = $this->AverageCalculator('ClientsMom', '12 months', $month);
            $ClientsTrialAverage = $this->AverageCalculator('clients', '12 months', $month);
            $ClientsHalfTrialAverage = $this->AverageCalculator('clients', '6 months', $month);
            $InvoiceTrialHalfMoM = $this->AverageCalculator('invoice_mom', '6 months', $month);
            $InvoiceTrialMoM = $this->AverageCalculator('invoice_mom', '12 months', $month);
            $InvoiceTrialHalf = $this->AverageCalculator('invoice', '6 months', $month);
            $InvoiceTrial= $this->AverageCalculator('invoice', '12 months', $month);
            $CollectionTrialHalf = $this->AverageCalculator('collection', '6 months', $month);
            $CollectionTrial= $this->AverageCalculator('collection', '12 months', $month);
            $CollectionTrialHalfMoM = $this->AverageCalculator('collection_mom', '6 months', $month);
            $CollectionTrialMoM = $this->AverageCalculator('collection_mom', '12 months', $month);
            $CollectionRateHalfTrial = $this->AverageCalculator('collection_rate', '6 months', $month);
            $CollectionRateTrial = $this->AverageCalculator('collection_rate', '12 months', $month);
            $CollectedOverBilled = $this->formula_calculator('collected', 'billed', $month);
            $WorkedOverAvailable = $this->formula_calculator('worked', 'available', $month);
            $BilledOverWorked = $this->formula_calculator('billed', 'worked', $month);
            $InvoiceOverCollection = $this->formula_calculator('invoice', 'collection', $month);
            $InvoiceOverExpense=$this->formula_calculator('invoice', 'expense', $month);
            $AllTimeRevenue = $this->formula_calculator('AllTimeRevenue', "", $month);
            $AllTimeOutstanding=$this->formula_calculator('AllTimeOutstanding', "", $month);
            $CollectionOverSales=$this->formula_calculator('collection', 'sales', $month);
            $ExpenseOverCollection=$this->formula_calculator('expense', 'collection', $month);
            $LegalMargin=$this->formula_calculator('LegalMargin', "", $month);
            return view('layouts.monthlyReports')->with("month",$month)
                ->with('firmName',$firm)
                ->with('Productivity',$Productivity)
                ->with('Financial',$Financial)
                ->with('Current',$AR['cur'])->with('Late',$AR['lat'])->with('Delinquent',$AR['del'])->with('Ar_Collection',$AR['col'])
                ->with('Green',$Green)->with('Red',$Red)->with('Yellow',$Yellow)->with('Count',$TotalMatters)
                ->with('NewClients',$NewClients)
                ->with('ClientsMoM',$ClientsMom)
                ->with('Utilization',$Utilization)->with('Realization',$Realization)->with('CollectionRate',$CollectionRate)
                ->with('AOP',$AOP)
                ->with('Top10ClientsByRevenue',$Top10ClientsByRevenue)->with('Top10ClientsByOutStanding',$Top10ClientsByOutStanding)
                ->with('FinancialTrial',$FinancialTrial)
                ->with('ProductivityTrial',$ProductivityTrial)
                ->with('ExpenseTrend',$ExpanseTrend)->with('ExpenseMom',$ExpenseMom)
                ->with('RevenueTrend',$RevenueTrend)->with('RevenueMom',$RevenueMom)
                ->with('CollectionTrend',$CollectionTrend)->with('CollectionMoM',$CollectionMom)
                ->with('ThisMonthNewClients',$ThisMonthNewClients)->with('ThisMonthClientsMom',$ThisMonthClientsMom)->with('LastMonthClientsMom',$LastMonthClientsMom)
                ->with('ThisMonthRevenue',$ThisMonthRevenue)->with('ThisMonthRevenueMom',$ThisMonthRevenueMom)->with('LastMonthRevenueMom',$LastMonthRevenueMom)->with('_2LastMonthRevenueMom',$_2LastMonthRevenueMom)
                ->with('ThisMonthCollectionTrend',$ThisMonthCollectionTrend)->with('ThisMonthCollectionMom',$ThisMonthCollectionMom)
                ->with('LastMonthCollectionMom',$LastMonthCollectionMom)->with('_2LastMonthCollectionMom',$_2LastMonthCollectionMom)
                ->with('NewMatters',$NewMatters)->with('ThisMonthNewMatters',$ThisMonthNewMatters)->with('NewMattersMom',$NewMattersMom)
                ->with('ClientsMomHalfTrialAverage',$ClientsMomHalfTrialAverage)
                ->with('ClientsMomTrialAverage',$ClientsMomTrialAverage)
                ->with('ClientsHalfTrialAverage',$ClientsHalfTrialAverage)
                ->with('ClientsTrialAverage',$ClientsTrialAverage)
                ->with('CollectionTrialHalf',$CollectionTrialHalf)->with('CollectionTrial',$CollectionTrial)
                ->with('BilledOverWorked',$BilledOverWorked)->with('CollectedOverBilled',$CollectedOverBilled)->with('WorkedOverAvailable',$WorkedOverAvailable)
                ->with('InvoiceOverCollection',$InvoiceOverCollection)->with('InvoiceOverExpense',$InvoiceOverExpense)
                ->with('InvoiceTrialMoM',$InvoiceTrialMoM)->with('InvoiceTrialHalfMoM',$InvoiceTrialHalfMoM)->with('InvoiceTrialHalf',$InvoiceTrialHalf)->with('InvoiceTrial',$InvoiceTrial)
                ->with('CollectionTrialHalfMoM',$CollectionTrialHalfMoM)->with('CollectionTrialMoM',$CollectionTrialMoM)
                ->with('CollectionRateHalfTrial',$CollectionRateHalfTrial)->with('CollectionRateTrial',$CollectionRateTrial)
                ->with('AllTimeRevenue',$AllTimeRevenue)->with('AllTimeOutStandings',$AllTimeOutstanding)
                ->with("NewMattersHalfTrialAverage",$NewMattersHalfTrialAverage)->with('NewMattersTrialAverage',$NewMattersTrialAverage)
                ->with('ExpenseOverCollection',$ExpenseOverCollection)->with("LegalMargin",$LegalMargin)->with("CollectionOverSales",$CollectionOverSales);

        }
        else
        {
            return redirect('/');
        }
    }

// Same Funtion But is for tables
    public function generateMonthlyTableReports(Request $request)
    {
        if(HelperLibrary::getFirmID()) {
            $firm = HelperLibrary::getFirm()->name;
            $Productivity = $this->ReportGenerator('productivity', date('Y'));
            $Financial = $this->ReportGenerator('financial', date('Y'));
            $Green = $this->ReportGenerator('MatterTracker', "", "green");
            $Red = $this->ReportGenerator('MatterTracker', '', 'red');
            $Yellow = $this->ReportGenerator('MatterTracker', "", 'yellow');
            $TotalMatters = $this->getTotalMatters();
            $Current = $this->ArReportGenerator('ar_current');
            $Late = $this->ArReportGenerator('ar_late');
            $Delinquent = $this->ArReportGenerator('ar_delinquent');
            $Ar_Collection = $this->ArReportGenerator('ar_collection');
            $ThisMonthNewClients = $this->ReportGenerator('ThisMonthNewClients');
            $ThisMonthClientsMom = $this->ReportGenerator('ThisMonthClientsMom');
            $LastMonthClientsMom = $this->ReportGenerator('LastMonthClientsMom');
            $ThisMonthRevenue = $this->ReportGenerator('ThisMonthRevenue');
            $ThisMonthRevenueMom = $this->ReportGenerator('ThisMonthRevenueMom');
            $LastMonthRevenueMom = $this->ReportGenerator('LastMonthRevenueMom');
            $_2LastMonthRevenueMom = $this->ReportGenerator('2LastMonthRevenueMom');
            $ThisMonthCollectionTrend = $this->ReportGenerator('ThisMonthCollectionTrend');
            $ThisMonthCollectionMom = $this->ReportGenerator('ThisMonthCollectionMom');
            $LastMonthCollectionMom = $this->ReportGenerator('LastMonthCollectionMom');
            $_2LastMonthCollectionMom = $this->ReportGenerator('2LastMonthCollectionMom');
            $NewClients = $this->ReportGenerator('NewClients');
            $ClientsMom = $this->ReportGenerator('ClientsMom');
            $Realization = $this->ReportGenerator('Realization');
            $Utilization = $this->ReportGenerator('Utilization');
            $CollectionRate = $this->ReportGenerator('Collection');
            $Top10ClientsByRevenue = $this->ReportGenerator('Top10ClientsByRevenue');
            $Top10ClientsByOutStanding = $this->ReportGenerator('Top10ClientsByOutStanding');
            $AOP = $this->ReportGenerator('AOP');
            $FinancialTrial = $this->ReportGenerator('FinancialTrial');
            $ProductivityTrial = $this->ReportGenerator('ProductivityTrial');
            $ExpanseTrend = $this->ReportGenerator('ExpenseTrend');
            $ExpenseMom = $this->ReportGenerator('ExpenseMom');
            $RevenueTrend = $this->ReportGenerator('RevenueTrend');
            $RevenueMom = $this->ReportGenerator('RevenueMom');
            $CollectionTrend = $this->ReportGenerator('CollectionTrend');
            $CollectionMom = $this->ReportGenerator('CollectionMom');
            $TimeEnteries = $this->ReportGenerator('TimeEnteries');
            $NewMatters = $this->ReportGenerator('NewMatters');
            $NewMattersMom = $this->ReportGenerator('NewMattersMom');
            $ThisMonthNewMatters = $this->ReportGenerator('ThisMonthNewMatters');
            $ClientsMomHalfTrialAverage = $this->AverageCalculator('ClientsMom', '6 months', "");
            $ClientsMomTrialAverage = $this->AverageCalculator('ClientsMom', '12 months', "");
            $NewMattersMomHalfTrialAverage = $this->AverageCalculator('NewMattersMom', "6 months", "");
            $NewMattersMomTrialAverage = $this->AverageCalculator('NewMattersMom', "12 months", "");
            $ClientsTrialAverage = $this->AverageCalculator('clients', '12 months', "");
            $ClientsHalfTrialAverage = $this->AverageCalculator('clients', '6 months', "");
            $InvoiceTrialHalfMoM = $this->AverageCalculator('invoice_mom', '6 months', "");
            $InvoiceTrialMoM = $this->AverageCalculator('invoice_mom', '12 months', "");
            $CollectionTrialHalfMoM = $this->AverageCalculator('collection_mom', '6 months', "");
            $CollectionTrialMoM = $this->AverageCalculator('collection_mom', '12 months', "");
            $CollectionRateHalfTrial = $this->AverageCalculator('collection_rate', '6 months', "");
            $CollectionRateTrial = $this->AverageCalculator('collection_rate', '12 months', "");
//            $ArCollectionHalfTrial = $this->AverageCalculator('Collection','6 months');
//            $ArCollectionTrial = $this->AverageCalculator('Collection','12 months');
            $CollectedOverBilled = $this->formula_calculator('collected', 'billed', "");
            $WorkedOverAvailable = $this->formula_calculator('worked', 'available', "");
            $BilledOverWorked = $this->formula_calculator('billed', 'worked', "");
            $InvoiceOverCollection = $this->formula_calculator('invoice', 'collection', "");
            $InvoiceOverExpense = $this->formula_calculator('invoice', 'expense', "");
            $AllTimeRevenue = $this->formula_calculator('AllTimeRevenue', "", "");
            $AllTimeOutstanding = $this->formula_calculator('AllTimeOutstanding', "", "");
            $CollectionOverSales=$this->formula_calculator('collection', 'sales', "");
            $ExpenseOverCollection=$this->formula_calculator('expense', 'collection', "");
            $LegalMargin=$this->formula_calculator('LegalMargin', "", "");
//            return view('layouts.monthlyTableReports')
//                ->with('firmName',$firm)
//                ->with('Productivity',$Productivity)
//                ->with('Financial',$Financial)
//                ->with('Current',$Current)->with('Late',$Late)->with('Delinquent',$Delinquent)->with('Ar_Collection',$Ar_Collection)
//                ->with('Green',$Green)->with('Red',$Red)->with('Yellow',$Yellow)->with('Count',$TotalMatters)
//                ->with('NewClients',$NewClients)
//                ->with('ClientsMoM',$ClientsMom)
//                ->with('Utilization',$Utilization)->with('Realization',$Realization)->with('CollectionRate',$CollectionRate)
//                ->with('AOP',$AOP)
//                ->with('Top10ClientsByRevenue',$Top10ClientsByRevenue)->with('Top10ClientsByOutStanding',$Top10ClientsByOutStanding)
//                ->with('FinancialTrial',$FinancialTrial)
//                ->with('ProductivityTrial',$ProductivityTrial)
//                ->with('ExpenseTrend',$ExpanseTrend)->with('ExpenseMom',$ExpenseMom)
//                ->with('RevenueTrend',$RevenueTrend)->with('RevenueMom',$RevenueMom)
//                ->with('CollectionTrend',$CollectionTrend)->with('CollectionMoM',$CollectionMom)
//                ->with('ThisMonthNewClients',$ThisMonthNewClients)->with('ThisMonthClientsMom',$ThisMonthClientsMom)->with('LastMonthClientsMom',$LastMonthClientsMom)
//                ->with('ThisMonthRevenue',$ThisMonthRevenue)->with('ThisMonthRevenueMom',$ThisMonthRevenueMom)->with('LastMonthRevenueMom',$LastMonthRevenueMom)->with('_2LastMonthRevenueMom',$_2LastMonthRevenueMom)
//                ->with('ThisMonthCollectionTrend',$ThisMonthCollectionTrend)->with('ThisMonthCollectionMom',$ThisMonthCollectionMom)
//                ->with('LastMonthCollectionMom',$LastMonthCollectionMom)->with('_2LastMonthCollectionMom',$_2LastMonthCollectionMom)
//                ->with('NewMatters',$NewMatters)->with('ThisMonthNewMatters',$ThisMonthNewMatters)->with('NewMattersMom',$NewMattersMom)
//                ->with('ClientsMomHalfTrialAverage',$ClientsMomHalfTrialAverage)
//                ->with('ClientsMomTrialAverage',$ClientsMomTrialAverage)
//                ->with('ClientsHalfTrialAverage',$ClientsHalfTrialAverage)
//                ->with('ClientsTrialAverage',$ClientsTrialAverage)
//                ->with("NewMattersMomHalfTrialAverage",$NewMattersMomHalfTrialAverage)->with('$NewMattersMomTrialAverage',$NewMattersMomTrialAverage)
//                ->with('BilledOverWorked',$BilledOverWorked)->with('CollectedOverBilled',$CollectedOverBilled)->with('WorkedOverAvailable',$WorkedOverAvailable)
//                ->with('InvoiceOverCollection',$InvoiceOverCollection)->with('InvoiceOverExpense',$InvoiceOverExpense)
//                ->with('InvoiceTrialMoM',$InvoiceTrialMoM)->with('InvoiceTrialHalfMoM',$InvoiceTrialHalfMoM)
//                ->with('CollectionTrialHalfMoM',$CollectionTrialHalfMoM)->with('CollectionTrialMoM',$CollectionTrialMoM)
//                ->with('CollectionRateHalfTrial',$CollectionRateHalfTrial)->with('CollectionRateTrial',$CollectionRateTrial)
//                ->with('AllTimeRevenue',$AllTimeRevenue)->with('AllTimeOutStandings',$AllTimeOutstanding)
//                ->with('ExpenseOverCollection',$ExpenseOverCollection)->with("LegalMargin",$LegalMargin)->with("CollectionOverSales",$CollectionOverSales);
//                   ->with('ArCollectionHalfTrial',$ArCollectionHalfTrial)->with('ArCollectionTrial',$ArCollectionTrial);

            $pdf = App::make('dompdf.wrapper');
            $pdf->loadHtml(view('layouts.experiment',array('firmName'=>$firm)));
            return $pdf->download("my.pdf");


          }

        else
        {
            return redirect('/login');
        }
//            return $this->AverageCalculator('new_clients','6 months');
    }





    public function TableReportGenerator($colum_name)
    {
        $ytd = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select($colum_name)
                 ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("this"), true))->sum("$colum_name");
        $temp = date('m');
//        var_dump($temp,$ytd,$ytd/8);
//        dd(Definition::getFinancialYear()->from);
        $current_fy_days_till_now = (new Carbon(Definition::getFinancialYear()->from))->diff(Carbon::now())->days;
        $ytd_avg = ($ytd / ($current_fy_days_till_now / 30));
        $fy_last = SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select($colum_name)
            ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getFinancialYear("last"), true))->sum("$colum_name");
        $fy_last_avg = $fy_last/12;  // average of last FY
        if($colum_name!='billable_hours')
        {
            $ytd=round($ytd/1000,0);
            $fy_last=round($fy_last/1000,0);
            $fy_last_avg=round($fy_last_avg/1000,0);
            $ytd_avg=round($ytd_avg/1000,0);
        }
        else
        {
            $ytd=round($ytd,0);
            $fy_last=round($fy_last,0);
            $fy_last_avg=round($fy_last_avg,0);
            $ytd_avg=round($ytd_avg,0);
        }
//          $data = date('Y')-2;
        return array('YTD',$ytd,'FY-last',$fy_last,'YTD_avg',$ytd_avg,'FY-last_avg',$fy_last_avg,'labels',Definition::getFinancialYearLabels());
    }
    public function ReportGenerator($reportType, $Year="", $color="")
    {
        if($reportType=="NewMattersMom")
        {
            return SummaryMonth::select('month','matters_mom')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year),true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="ProductivityTrial")
        {
            return SummaryMonth::select('month','available_time','billed_time','collected_time','worked_time')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year),true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="TimeEnteries")
        {
            return SummaryMonth::select('month','billable_hours','billed_hours')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getYearTrail(),true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="NewMatters")
        {
            return SummaryMonth::select('month','new_matters')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year),true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="FinancialTrial")
        {
            return SummaryMonth::where("firm_id", HelperLibrary::getFirmID())->select(["revenue", "expense", "collection","month"])
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year), true))->get();
        }
        if($reportType=="AOP")
        {
            return SummaryAOP::where("firm_id", HelperLibrary::getFirmID())
                ->select(["name", "clients"])
                ->get();
        }
        if($reportType=="Top10ClientsByRevenue")
        {
            return SummaryClient::Select('client_name','revenue')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where('revenue','<>',0)
                ->orderby("revenue", "desc")
                ->limit(10)->get();
        }
        if($reportType=="Top10ClientsByOutStanding")
        {
            return SummaryClient::Select('client_name','outstanding_dues')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where('outstanding_dues','<>',0)
//                ->whereIn('month',HelperLibrary::getMonthsFromRange(Definition::))
                ->orderby("outstanding_dues", "desc")
                ->limit(10)->get(10);
        }
        if($reportType=="financial")
        {
            return SummaryMonth::select('month','revenue','revenue_avg_rate','revenue_percentage_growth','expense','expense_percentage_growth','collection','collection_percentage_growth')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrail(), true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="productivity")
        {
            return SummaryMonth::select('month','available_time','billed_time','collected_time','worked_time','billed_vs_collected')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getYearTrail(),true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="MatterTracker")
        {
             return $this->MatterTrackerReport($color);
        }
        if($reportType=="NewClients")
        {
            return SummaryMonth::select('new_clients','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="ThisMonthNewClients")
        {
            return SummaryMonth::select('new_clients','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime($Year)-1))
                ->get();
        }
        if($reportType=="ThisMonthNewMatters")
        {
            return SummaryMonth::select('new_matters','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime($Year)-1))
                ->get();
        }
        if($reportType=="ThisMonthRevenue")
        {
            return SummaryMonth::select('revenue','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime($Year)-1))
                ->get();
        }
        if($reportType=="ThisMonthRevenueMom")
        {
            return SummaryMonth::select('revenue_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime($Year)-1))
                ->get();
        }
        if($reportType=="LastMonthRevenueMom")
        {
            return SummaryMonth::select('revenue_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime($Year)-1))
                ->get();
        }
        if($reportType=="2LastMonthRevenueMom")
        {
            return SummaryMonth::select('revenue_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime($Year)-3))
                ->get();
        }

        if($reportType=="ClientsMom")
        {
            return SummaryMonth::select('clients_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="ThisMonthClientsMom")
        {
            return SummaryMonth::select('clients_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime($Year)-1))
                ->get();
        }
        if($reportType=="LastMonthClientsMom")
        {
            return SummaryMonth::select('clients_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime($Year)-2))
                ->get();
        }
        if($reportType=="2LastMonthClientsMom")
        {
            return SummaryMonth::select('clients_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime($Year)-3))
                ->get();
        }

        if($reportType=="ExpenseTrend")
        {
            return SummaryMonth::select('expense','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrail(), true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="ExpenseMom")
        {
            return SummaryMonth::select('expense_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrail(), true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="RevenueTrend")
        {
            return SummaryMonth::select('revenue','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                ->orderby('month','asc')
                ->get();
        }

        if($reportType=="RevenueMom")
        {
            return SummaryMonth::select('revenue_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="CollectionTrend")
        {
            return SummaryMonth::select('collection','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="ThisMonthCollectionTrend")
        {
            return SummaryMonth::select('collection','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime($Year)-1))
                ->get();
        }
        if($reportType=="CollectionMom")
        {
            return SummaryMonth::select('collection_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=="ThisMonthCollectionMom")
        {
            return SummaryMonth::select('collection_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime($Year)-1))
                ->get();
        }
        if($reportType=="LastMonthCollectionMom")
        {
            return SummaryMonth::select('collection_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime("-2 month")))
                ->get();
        }
        if($reportType=="2LastMonthCollectionMom")
        {
            return SummaryMonth::select('collection_mom','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->where("month",date('Y-m-01', strtotime("-3 month")))
                ->get();
        }

        if($reportType=='Utilization')
        {
            return SummaryMonth::select('utilization_rate','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrail(), true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=='Realization')
        {
            return SummaryMonth::select('realization_rate','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrail(), true))
                ->orderby('month','asc')
                ->get();
        }
        if($reportType=='Collection')
        {
            return SummaryMonth::select('collection_rate','month')
                ->where('firm_id','=',HelperLibrary::getFirmID())
                ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrail(), true))
                ->orderby('month','asc')
                ->get();
        }

    }
    public function MatterTrackerReport($color)
    {
        return SummaryMatterTracker::select('type')
            ->where('type','=',$color)
            ->where('firm_id','=',HelperLibrary::getFirmID())
            ->count('type');
    }

    public function ArReportGenerator($column_name)
    {
        return SummaryAllTime::select($column_name)->where('firm_id','=',HelperLibrary::getFirmID())->sum($column_name);
    }
    public function ArAging($month)
    {
        $cd = date("Y-m-01 00:00:00",strtotime($month)-1);
        $cd = \DateTime::createFromFormat("Y-m-d 00:00:00", $cd);
        $current_month = date("Y-m-01 00:00:00",strtotime($month));
//        $from = date("Y-m-01 00:00:00", strtotime('-5 months', strtotime($to)));
        $definitions = Definition::getInvoicesDefinitions(HelperLibrary::getFirmID());
        if (HelperLibrary::getFirmIntegration()=="practice_panther") {
            $time_field = "issue_date";
            $balance_field = "total_outstanding";
            $invoices = PPInvoice::where("firm_id", HelperLibrary::getFirmID())->where("invoice_type", "sale")
                ->where("total_outstanding", ">", "0")->where("issue_date","<",$current_month);
        } else {
            $time_field = "issued_at";
            $balance_field = "due";
            $invoices = CLInvoice::where("firm_id", HelperLibrary::getFirmID())
                ->whereNotIn("state", ["deleted", "void"])
                ->where("due", ">", "0")->where("issued_at","<",$current_month);
        }
        $cur = 0;
        $lat = 0;
        $del = 0;
        $col = 0;
        foreach ($invoices->get() as $inv) {
            $id = new \DateTime(substr($inv->{$time_field}, 0, 10));
            $diff = $id->diff($cd)->format("%a");
            if ($diff >= $definitions->current_from && $diff <= $definitions->current_to) {
                $cur += round($inv->{$balance_field}, 0);
            } elseif ($diff >= $definitions->late_from && $diff <= $definitions->late_to) {
                $lat += round($inv->{$balance_field}, 0);
            } elseif ($diff >= $definitions->delinquent_from && $diff <= $definitions->delinquent_to) {
                $del += round($inv->{$balance_field}, 0);
            } elseif ($diff >= $definitions->collection_from && $diff <= $definitions->collection_to) {
                $col += round($inv->{$balance_field}, 0);
            }
        }
        return array('cur'=>$cur,"lat"=>$lat,"del"=>$del,"col"=>$col);

    }
    public function getTotalMatters()
    {
        return SummaryMatterTracker::select('type')
            ->where('firm_id','=',HelperLibrary::getFirmID())
            ->count('type');
    }
    public function AverageCalculator($column_name, $type, $Year="")
    {
        if($column_name=='clients')
        {
            if($type=='6 months')
            {
                $data = SummaryMonth::select('new_clients', 'month')
                    ->where('firm_id', '=', HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year), true))
                    ->orderby('month', 'asc')
                    ->sum('new_clients');
                return round(($data / 6),2);
            }
            else
            {
                $data = SummaryMonth::select('new_clients', 'month')
                    ->where('firm_id', '=', HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                    ->orderby('month', 'asc')
                    ->sum('new_clients');
                return round(($data / 12),2);
            }
        }

        if($column_name=='ClientsMom')
        {
            if($type=='6 months')
            {
                $data = SummaryMonth::select('clients_mom','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year), true))
                    ->orderby('month','asc')
                    ->sum('clients_mom');
                return round(($data/6),2);
            }
            else
            {
                $data = SummaryMonth::select('clients_mom','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                    ->orderby('month','asc')
                    ->sum('clients_mom');
                return round(($data/12),2);
            }

        }
        if($column_name=='Late')
        {
            if($type=='6 months')
            {
                $data = SummaryAllTime::select('ar_late','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverage(), true))
                    ->orderby('month','asc')
                    ->sum('ar_late');
                return round(($data/6),2);
            }
            else
            {
                $data = SummaryAllTime::select('ar_current','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrail(), true))
                    ->orderby('month','asc')
                    ->sum('ar_late');
                return round(($data/12),2);
            }

        }
        if($column_name=='Current')
        {
            if($type=='6 months')
            {
                $data = SummaryAllTime::select('ar_current','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverage(), true))
                    ->orderby('month','asc')
                    ->sum('ar_current');
                return round(($data/6),2);
            }
            else
            {
                $data = SummaryMonth::select('ar_current','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrail(), true))
                    ->orderby('month','asc')
                    ->sum('ar_current');
                return round(($data/12),2);
            }

        }
        if($column_name=='Collection')
        {
            if($type=='6 months')
            {
                $data = SummaryAllTime::select('ar_collection','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereRaw("created_at","LIKE", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverage(), true)."%")
                    ->orderby('month','asc')
                    ->sum('ar_collection');
                return round(($data/6),2);
            }
            else
            {
                $data = SummaryMonth::select('ar_collection','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrail(), true))
                    ->orderby('month','asc')
                    ->sum('ar_collection');
                return round(($data/12),2);
            }

        }
        if($column_name=='collection_mom')
        {
            if($type=='6 months')
            {
                $data = SummaryMonth::select('collection_mom', 'month')
                    ->where('firm_id', '=', HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year), true))
                    ->orderby('month', 'asc')
                    ->sum('collection_mom');
                return round((($data / 6)),2);
            }
            else
            {
                $data = SummaryMonth::select('collection_mom', 'month')
                    ->where('firm_id', '=', HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                    ->orderby('month', 'asc')
                    ->sum('collection_mom');
                return round(($data / 12),2);
            }
        }
        if($column_name=='collection_rate')
        {
            if($type=='6 months')
            {
                $data = SummaryMonth::select('collection_rate', 'month')
                    ->where('firm_id', '=', HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year), true))
                    ->orderby('month', 'asc')
                    ->sum('collection_rate');
                return round((($data / 6)),2);
            }
            else
            {
                $data = SummaryMonth::select('collection_rate', 'month')
                    ->where('firm_id', '=', HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                    ->orderby('month', 'asc')
                    ->sum('collection_rate');
                return round(($data / 12),2);
            }
        }
        if($column_name=='invoice_mom')
        {
            if($type=='6 months')
            {
                $data = SummaryMonth::select('revenue_mom', 'month')
                    ->where('firm_id', '=', HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year), true))
                    ->orderby('month', 'asc')
                    ->sum('revenue_mom');
                return round((($data / 6)),2);
            }
            else
            {
                $data =  SummaryMonth::select('revenue_mom','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                    ->orderby('month','asc')
                    ->sum('revenue_mom');
                return round(($data/12),2);
            }
        }
        if($column_name=='invoice')
        {
            if($type=='6 months')
            {
                $data = SummaryMonth::select('revenue', 'month')
                    ->where('firm_id', '=', HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year), true))
                    ->orderby('month', 'asc')
                    ->sum('revenue');
                return round((($data / 6)),2);
            }
            else
            {
                $data =  SummaryMonth::select('revenue','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                    ->orderby('month','asc')
                    ->sum('revenue');
                return round(($data/12),2);
            }
        }

        if($column_name=='collection')
        {
            if($type=='6 months')
            {
                $data = SummaryMonth::select('collection', 'month')
                    ->where('firm_id', '=', HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverage(), true))
                    ->orderby('month', 'asc')
                    ->sum('collection');
                return round((($data / 6)),2);
            }
            else
            {
                $data =  SummaryMonth::select('collection','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrail(), true))
                    ->orderby('month','asc')
                    ->sum('collection');
                return round(($data/12),2);
            }

        }
        if($column_name=='NewMatters')
        {
            if($type=='6 months')
            {
                $data = SummaryMonth::select('new_matters', 'month')
                    ->where('firm_id', '=', HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year), true))
                    ->orderby('month', 'asc')
                    ->sum('new_matters');
                return round((($data / 6)),2);
            }
            else
            {
                $data =  SummaryMonth::select('new_matters','month')
                    ->where('firm_id','=',HelperLibrary::getFirmID())
                    ->whereIn("month", HelperLibrary::getMonthsFromRange(Definition::getYearTrailCustom($Year), true))
                    ->orderby('month','asc')
                    ->sum('new_matters');
                return round(($data/12),2);
            }


        }
    }
    public function formula_calculator($calculate, $over, $Year="")
    {

        $expense = SummaryMonth::select('expense')
            ->where('firm_id','=',HelperLibrary::getFirmID())
            ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year),true))
            ->orderby('month','asc')
            ->sum('expense');
        $worked = SummaryMonth::select('worked_time')
            ->where("firm_id",HelperLibrary::getFirmID())
            ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year),true))
            ->orderby('month','asc')
            ->sum('worked_time');
        $available = SummaryMonth::select('available_time')
            ->where('firm_id','=',HelperLibrary::getFirmID())
            ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year),true))
            ->orderby('month','asc')
            ->sum('available_time');
        $billed = SummaryMonth::select('billed_time')
            ->where('firm_id','=',HelperLibrary::getFirmID())
            ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year),true))
            ->orderby('month','asc')
            ->sum('billed_time');
        $collected = SummaryMonth::select('collected_time')
            ->where('firm_id','=',HelperLibrary::getFirmID())
            ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year),true))
            ->orderby('month','asc')
            ->sum('collected_time');
        $revenue = SummaryMonth::select('revenue')
            ->where('firm_id','=',HelperLibrary::getFirmID())
            ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year),true))
            ->orderby('month','asc')
            ->sum('revenue');
        $collection = SummaryMonth::select('collection')
            ->where('firm_id','=',HelperLibrary::getFirmID())
            ->whereIn("month",HelperLibrary::getMonthsFromRange(Definition::getHalfYearTrailAverageCustom($Year),true))
            ->orderby('month','asc')
            ->sum('collection');
        if($calculate=="LegalMargin")
        {
           $temp = $revenue - $expense;
           return round(($temp/6),2);

        }
        if($calculate=="collection" && $over=="sales") {
            $collection = ($collection / 6);
            $revenue = ($revenue / 6);
            if ($revenue > 0) {
                return round((($collection / $revenue) * 100), 2);
            }
            return 0;
        }
        if($calculate=="expense" && $over=="collection") {
            $expense = ($expense / 6);
            $collection = ($collection / 6);
            if ($collection > 0) {
                return round((($expense / $collection) * 100), 2);
            }
            return 0;
        }

        if($calculate=='AllTimeRevenue')
        {
            $clientrevenue=0;
            $data_raw = SummaryClient::select('client_id','revenue')
                ->where('firm_id',HelperLibrary::getFirmID())
                ->where('revenue','>',0)
                ->orderby('revenue','desc')
                ->limit(10)->get();
            if(HelperLibrary::getFirmIntegration()=='practice_panther')
            {
                $totalrevenue = PPInvoice::where("firm_id", HelperLibrary::getFirmID())
                    ->where('invoice_type','Sale')
                    ->where('total', ">", 0)
                    ->sum("total");

            }
            else
             {
                 $totalrevenue = CLInvoice::where("firm_id", HelperLibrary::getFirmID())->whereNotIn("state", ["deleted", "void"])->sum("total");
             }
            for($i=0;$i<10;$i++)
            {
                $clientrevenue+= $data_raw[$i]->revenue;
            }
            if($totalrevenue>0) {
                return round((($clientrevenue / $totalrevenue) * 100), 2);
            }else
            {
                return 0;
            }
        }
        if($calculate=='AllTimeOutstanding')
        {
            $clientoutstanding=0;
            $data_raw = SummaryClient::select('client_id','outstanding_dues')
                ->where('firm_id',HelperLibrary::getFirmID())
                ->where('outstanding_dues','>',0)
                ->orderby('outstanding_dues','desc')
                ->limit(10)->get();
            if(HelperLibrary::getFirmIntegration()=="practice_panther")
            {
                $totaloutstanding = PPInvoice::where("firm_id", HelperLibrary::getFirmID())
                    ->where('invoice_type','Sale')
                    ->where('total_outstanding', ">", 0)
                    ->sum("total_outstanding");
            }
            else
            {
                $totaloutstanding = CLInvoice::where("firm_id", HelperLibrary::getFirmID())->whereNotIn("state", ["deleted", "void"])->sum("due");
            }
            for($i=0;$i<10;$i++)
            {
                $clientoutstanding+= $data_raw[$i]->outstanding_dues;
            }
            if($totaloutstanding>0)
            {
                return round((($clientoutstanding/$totaloutstanding)*100),2);
            }
            else
            {
                return 0;
            }
        }
        if($calculate=='worked' && $over=='available')
        {
            if($available>0)
            {
                return round(($worked/$available)*100,2);
//                    return $worked;
            }

            else
            {
                return 0;
            }
        }
        if($calculate=='billed' && $over=='worked')
        {
            $billed = ($billed/6);
            $worked = ($worked/6);
            if($worked>0)
            {
                return round(($billed / $worked) * 100, 2);
            }
            else
            {
                return 0;
            }
        }
        if($calculate=='collected' && $over=='billed')
        {
            $collected = ($collected/6);
            $billed = ($billed/6);
            if($billed>0)
            {
                return round(($collected/$billed)*100,2);
            }
            return 0;

        }
        if($calculate=='invoice' && $over=='expense') {
            $revenue = ($revenue / 6);
            $expense = ($expense / 6);
            if ($expense > 0) {
                return round(($revenue / $expense) * 100, 2);
            }
            else
            {
                return 0;
            }
        }
        if($calculate=='invoice' && $over=='collection') {
            $revenue = ($revenue / 6);
            $collection = ($collection / 6);
            if ($collection > 0) {
                return round(($revenue / $collection) * 100, 2);
            }
            else
            {
                return 0;
            }
        }

    }
    function checkMonth($month)
    {
        if($month=="2017-1" || $month=="2017-01")
        {
            $month="2017";
        }
        $year=substr($month,0,4);
        $last_month="";
        $new_month="";
        $new_month = substr($month,5);
         if($year<"2017" || $month=="oldest")
         {
            return "2017-12";
         }
        if(strlen($new_month)==1)
        {
            $month = "0".$new_month;
            $month = $year."-".$month;
        }
        if(strpos($month,"-")==false && $month==date('Y'))
        {
            $year = date("Y");
            $last_month = date("m");
            return $year."-".$last_month;
        }
        if(strpos($month,"-")==false && $month<date('Y') && $month>"2016")
        {
            $year = substr($month,0,4);
            $last_month ="12";
            return $year."-".$last_month;
        }
        if ($month>date("Y-m"))
        {
            $year = date("Y");
            $last_month = date("m");
            return $year."-".$last_month;
        }
        else
        {
            return $month;
        }

    }




}
