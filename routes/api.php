<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::get('/dashboard/matter-tracker', function(){
//    return response()->json([]);
//});

Route::any('/update', "FirmTrakController@update");

Route::group(["middleware"=>"CustomAuth", "prefix"=>"app"], function(){
    // Profile
    Route::get('/profile', 'AuthController@profile');
    Route::post('/profile', 'AuthController@profileSave');
    Route::put('/profile', 'AuthController@profilePasswordSave');
    Route::get('/package/permissions', 'AuthController@packagePermission');
    // Menu
    Route::resource('/menu', 'MenuController');
    Route::get('/load/menu', 'MenuController@generatemenu');
    // Firms
    Route::post('/firms/change-plan', 'FirmController@changePlan');
    Route::resource('/firms', 'FirmController');
    // Firm Users
    Route::get('/firm-users/activity-logs', 'FirmUserController@activityLogs');
    Route::resource('/firm-users', 'FirmUserController');
    // Firm Integration
    Route::resource('/firm-integration', 'FirmIntegrationController');
    // Firm Roles
    Route::resource('/firm-roles', 'FirmRoleController');
    // Firm Plan
    Route::get('/firm-plan/cancel', 'FirmPlanController@cancel');
    Route::resource('/firm-plan', 'FirmPlanController');
    // Dashboard
    Route::get('/dashboard', 'DashboardController@index');
    Route::get('/dashboard/contacts', 'DashboardController@getContactsData'); /* Deprecated */
    Route::get('/dashboard/matters', 'DashboardController@getMattersData'); /* Deprecated */
    Route::get('/dashboard/financials', 'DashboardController@getFinancialsData');
    Route::get('/dashboard/ar', 'DashboardController@getARData');
    Route::get('/dashboard/utilization', 'DashboardController@getUtilizationData'); /* Deprecated */
    Route::get('/dashboard/realization', 'DashboardController@getRealizationData'); /* Deprecated */
    Route::get('/dashboard/collection', 'DashboardController@getCollectionData'); /* Deprecated */
    Route::get('/dashboard/urc', 'DashboardController@getURCData'); /* Deprecated */
    Route::get('/dashboard/productivity', 'DashboardController@getProductivityData');
    Route::get('/dashboard/matter-tracker', 'DashboardController@getMatterTrackerData');
    Route::get('/dashboard/revenue', 'RECController@getRevenueCardData');
    Route::get('/dashboard/billables', 'RECController@getBillablesCardData');
    Route::get('/dashboard/expenses', 'RECController@getExpensesCardData');
    Route::get('/dashboard/collections', 'RECController@getCollectionsCardData');

    //writeOff
    Route::get('/client-management/clientwrittenoff', 'ClientManagementController@getClientWrittenoff');
    Route::get('/kpi/employeewrittenoff', 'KPIController@getEmployeeWrittenoff');
    //firmReports
    Route::get('/firm/salesreports', 'FirmController@firmSalesReport');
    Route::get('/contact/salesreports', 'FirmController@contactSalesReport');
    // Definition
    Route::resource('/definition', 'DefinitionController');
    // Matters WBS
    Route::resource('/matters-wbs', 'MattersWBSController');
    Route::get('/matter-types', 'MattersWBSController@getMatterTypes');
    Route::get('/getOriginatingAttorney', 'MattersWBSController@getOriginatingAttorney');
    Route::get('/user-types', 'MattersWBSController@getUserTypes');
    // FTE Setup
    Route::resource('/fte-setup', 'FTESetupController');
    // Matters
    Route::get('/matters/timeline/{id}', 'MatterController@timeline');
    Route::get('/matters/all', 'MatterController@all');
    Route::get('/matters/red', 'MatterController@ragRed');
    Route::get('/matters/yellow', 'MatterController@ragYellow');
    Route::get('/matters/green', 'MatterController@ragGreen');
    Route::get('/matters/trend', 'MatterController@trend');
    Route::get('/matters/mom', 'MatterController@mom');
    Route::get('/matters/mts', 'MatterController@mts');
    Route::get('/matters/top10-by-revenue', 'MatterController@top10ByRevenue');
    Route::get('/matters/top10-by-outstanding', 'MatterController@top10ByOutstanding');
    Route::get('/matters/new-matters', 'MatterController@newMatters');
     // AR
    Route::get('/ar/all', 'ARController@all');
    Route::get('/ar/current', 'ARController@current');
    Route::get('/ar/late', 'ARController@late');
    Route::get('/ar/delinquent', 'ARController@delinquent');
    Route::get('/ar/collection', 'ARController@collection');
    // Contacts
    Route::get('/contacts/trend', 'ContactController@trend');
    Route::get('/contacts/mom', 'ContactController@mom');
    Route::get('/contacts/mts', 'ContactController@mts');
    Route::get('/contacts/top10-by-revenue', 'ContactController@top10ByRevenue');
    Route::get('/contacts/top10-by-outstanding', 'ContactController@top10ByOutstanding');
    Route::get('/contacts/new-clients', 'ContactController@newClients');
    Route::get('/contacts/new-clients-new', 'ContactController@new_clients_new');
    // KPI
    //project Management (productivity)
    Route::get('/kpi/productivityWorked', 'KPIController@productivityWorkedTime');
    Route::get('/kpi/productivityWorkedMOM', 'KPIController@productivityWorkedMOM');
    Route::get('/kpi/productivityAvailable', 'KPIController@productivityAvailableTime');
    Route::get('/kpi/productivityAvailableMOM', 'KPIController@productivityAvailableMOM');
    Route::get('/kpi/productivityBilled', 'KPIController@productivityBilledTime');
    Route::get('/kpi/productivityBilledMOM', 'KPIController@productivityBilledMOM');
    Route::get('/kpi/productivityCollected', 'KPIController@productivityCollectedTime');
    Route::get('/kpi/productivityCollectedMOM', 'KPIController@productivityCollectedMOM');
    Route::get('/kpi/productivityAll', 'KPIController@getProductivityAll_perUser');
    Route::get('/kpi/userWorkedTime', 'KPIController@getUserWorkTime');
    Route::get('/kpi/matters/open-vs-close', 'KPIController@openVscloseMatters');


    //project Management (billables)
    Route::get('/kpi/billablesMonthlyMom', 'KPIController@getBillables_perUser');
    Route::get('/kpi/nonBillablesMonthlyMom', 'KPIController@getNonBillables_perUser');
    Route::get('/kpi/billableVsNonBillable', 'KPIController@getBillablesVsNonBillables_perUser');

    //
    Route::get('/kpi/utilization/trend', 'KPIController@utilizationTrend');
    Route::get('/kpi/utilization/mom', 'KPIController@utilizationMom');
    Route::get('/kpi/utilization/by-attorney', 'KPIController@utilizationByAttorney');
    Route::get('/kpi/utilization/by-users', 'KPIController@utilizationByUsers');
    Route::get('/kpi/utilization/by-matter-types', 'KPIController@utilizationByMatterTypes');
    Route::get('/kpi/utilization/attorney-vs-paralegal', 'KPIController@utilizationAttorneyVsParalegalStaff');
    Route::get('/kpi/realization/overall', 'KPIController@realizationOverall');
    Route::get('/kpi/realization/trend', 'KPIController@realizationTrend');
    Route::get('/kpi/realization/mom', 'KPIController@realizationMom');
    Route::get('/kpi/realization/by-attorney', 'KPIController@realizationByAttorney');
    Route::get('/kpi/realization/by-users', 'KPIController@realizationByUsers');
    Route::get('/kpi/realization/by-matter-types', 'KPIController@realizationByMatterTypes');
    Route::get('/kpi/realization/attorney-vs-paralegal', 'KPIController@realizationAttorneyVsParalegalStaff');
    Route::get('/kpi/collection/trend', 'KPIController@collectionTrend');
    Route::get('/kpi/collection/mom', 'KPIController@collectionMom');
    Route::get('/kpi/collection/by-attorney', 'KPIController@collectionByAttorney');
    Route::get('/kpi/collection/by-legal-staff', 'KPIController@collectionByLegalStaff');
    Route::get('/kpi/collection/by-users', 'KPIController@collectionByUsers');
    Route::get('/kpi/collection/by-matter-types', 'KPIController@collectionByMatterTypes');
    Route::get('/kpi/collection/attorney-vs-paralegal', 'KPIController@collectionAttorneyVsParalegalStaff');
    // Revenue, Expense, Collection
    Route::get('/new-revenue-new', 'FinancialManagementController@new_revenue_new');
    Route::get('/new-collection-new', 'FinancialManagementController@new_collection_new');
    Route::get('/new-expense-new', 'FinancialManagementController@new_expense_new');
    Route::get('/rec/revenue/trend', 'FinancialManagementController@revenueTrend');
    Route::get('/rec/revenue/by-fte', 'RECController@revenueByFTE');
    Route::get('/rec/revenue/by-attorney', 'RECController@revenueByAttorney');
    Route::get('/rec/revenue/by-legal-staff', 'RECController@revenueByLegalStaff');
    Route::get('/rec/revenue/by-sr-associate', 'RECController@revenueBySrAssosiate');
    Route::get('/rec/revenue/by-jr-associate', 'RECController@revenueByJrAssosiate');
    Route::get('/rec/revenue/by-matter-type', 'RECController@revenueByMatterType');
    Route::get('/rec/revenue/mom', 'FinancialManagementController@revenueMom');
    Route::get('/rec/revenue/data', 'RECController@revenueData');
    Route::get('/rec/revenue/single-data', 'RECController@revenueSingleData');
    Route::get('/rec/revenue/diagnostics/trend', 'RECController@revenueDiagnosticsTrend');
    Route::get('/rec/revenue/diagnostics/by-attorney-partner', 'RECController@revenueDiagnosticsByAttorneyPartner');
    Route::get('/rec/revenue/diagnostics/by-sr-associate', 'RECController@revenueDiagnosticsBySrAssociate');
    Route::get('/rec/revenue/diagnostics/by-jr-associate', 'RECController@revenueDiagnosticsByJrAssociate');
    Route::get('/rec/revenue/diagnostics/by-paralegal-staff', 'RECController@revenueDiagnosticsByParalegalStaff');
    Route::get('/rec/revenue/predictive/trend', 'RECController@revenuePredictiveTrend');
    Route::get('/rec/revenue/predictive/by-attorney-partner', 'RECController@revenuePredictiveByAttorneyPartner');
    Route::get('/rec/revenue/predictive/by-sr-associate', 'RECController@revenuePredictiveBySrAssociate');
    Route::get('/rec/revenue/predictive/by-jr-associate', 'RECController@revenuePredictiveByJrAssociate');
    Route::get('/rec/revenue/predictive/by-paralegal-staff', 'RECController@revenuePredictiveByParalegalStaff');
    Route::get('/rec/gross-profit-margin/overall', 'RECController@grossProfitMarginOverall');
    Route::get('/rec/collection/data', 'RECController@collectionData');
    Route::get('/rec/collection/single-data', 'RECController@collectionSingleData');
    Route::get('/rec/collection/trend', 'FinancialManagementController@collectionTrend');
    Route::get('/rec/collection/mom', 'FinancialManagementController@collectionMom');
    Route::get('/rec/collection/by-fte', 'RECController@collectionByFTE');
    Route::get('/rec/collection/by-attorney', 'RECController@collectionByAttorney');
    Route::get('/rec/collection/by-legal-staff', 'RECController@collectionByLegalStaff');
    Route::get('/rec/collection/by-matter-type', 'RECController@collectionByMatterType');
    Route::get('/rec/expense/trend', 'FinancialManagementController@expenseTrend');
    Route::get('/rec/expense/mom', 'FinancialManagementController@expenseMom');
    Route::get('/rec/expense/by-fte', 'RECController@expenseByFTE');
    Route::get('/rec/expense/by-attorney', 'RECController@expenseByAttorney');
    Route::get('/rec/expense/by-legal-staff', 'RECController@expenseByLegalStaff');
    Route::get('/rec/expense/by-matter-type', 'RECController@expenseByMatterType');
    Route::get('/rec/expense/data', 'RECController@expenseData');
    Route::get('/rec/expense/single-data', 'RECController@expenseSingleData');
    Route::get('/rec/expense/diagnostics/trend', 'RECController@expenseDiagnosticsTrend');
    Route::get('/rec/expense/diagnostics/by-attorney-partner', 'RECController@expenseDiagnosticsByAttorneyPartner');
    Route::get('/rec/expense/diagnostics/by-sr-associate', 'RECController@expenseDiagnosticsBySrAssociate');
    Route::get('/rec/expense/diagnostics/by-jr-associate', 'RECController@expenseDiagnosticsByJrAssociate');
    Route::get('/rec/expense/diagnostics/by-paralegal-staff', 'RECController@expenseDiagnosticsByParalegalStaff');
    Route::get('/rec/expense/predictive/trend', 'RECController@expensePredictiveTrend');
    Route::get('/rec/expense/predictive/by-attorney-partner', 'RECController@expensePredictiveByAttorneyPartner');
    Route::get('/rec/expense/predictive/by-sr-associate', 'RECController@expensePredictiveBySrAssociate');
    Route::get('/rec/expense/predictive/by-jr-associate', 'RECController@expensePredictiveByJrAssociate');
    Route::get('/rec/expense/predictive/by-paralegal-staff', 'RECController@expensePredictiveByParalegalStaff');
    // Financials
    Route::get('/financials/descriptive', 'FinancialsController@getDescriptive');
    Route::get('/financials/diagnostic', 'FinancialsController@getDiagnostic');
    Route::get('/financials/predictive', 'FinancialsController@getPredictive');
    // Productivity
    Route::get('/productivity/descriptive', 'ProductivityController@getDescriptive');
    Route::get('/productivity/diagnostic', 'ProductivityController@getDiagnostic');
    Route::get('/productivity/predictive', 'ProductivityController@getPredictive');
    // Clients Management
    Route::get('/client-management/new-clients-per-attorney', 'ClientManagementController@newClientsPerAttorney');
    Route::get('/client-management/new-matters-per-attorney', 'ClientManagementController@newMattersPerAttorney');
    Route::get('/client-management/new-clients-per-aop', 'ClientManagementController@newClientsPerAOP');
    Route::get('/client-management/new-matters-per-aop', 'ClientManagementController@newMattersPerAOP');
    Route::get('/client-management/top-5-aop-by-revenue', 'ClientManagementController@top5AOPByRevenue');
    Route::get('/client-management/top-5-aop-by-outstanding-dues', 'ClientManagementController@top5AOPByOutstandingDues');
    Route::get('/client-management/top-5-aop-by-gpm', 'ClientManagementController@top5AOPByGPM');
    Route::get('/client-management/aop-clients', 'AOPController@getAOPClients');
    Route::get('/client-management/time-keepers', 'ClientManagementController@getTimeKeepers');
    Route::get('/client-management/client-types', 'ClientManagementController@getClientsTypePerMonth');
    // Matter Management
    Route::get('/matter-management/aop-matters', 'AOPController@getAOPMatters');
    Route::get('/matter-management/time-keepers', 'MatterManagementController@getTimeKeepers');
    Route::get('/matter-management/matterstype-per-clients', 'MatterManagementController@getMattersPerUserType');
    // Financial Management
    Route::get('/financial-management/aop-by-revenue', 'FinancialManagementController@allAOPByRevenue');
    Route::get('/financial-management/aop-by-gpm', 'FinancialManagementController@allAOPByGpm');
    Route::get('/financial-management/revenue-gross-profit-margin', 'FinancialManagementController@revenueVsGrossProfitMargin');
    // Project Management
    Route::get('/project-management/open-tasks-per-user', 'ProjectManagementController@tasksPerUsersOpen');
    Route::get('/project-management/overdue-tasks-per-user', 'ProjectManagementController@tasksPerUsersOverdue');
    // Settings
    Route::get('/settings', 'SettingsController@viewSettings');
    Route::post('/settings', 'SettingsController@saveSettings');
    // Practice Areas
    Route::resource('/practice-areas', 'PracticeAreaController');
    // Firm Packages
    Route::resource('/firm-packages', 'FirmPackageController');
    // Follow Ups
    Route::resource('/followups', 'FollowupController');
    // Support Module
    Route::get('/support/dashboard', 'Support\DashboardController@index');
    Route::resource('/support/tickets', 'Support\TicketController');
    Route::resource('/support/ticket-replies', 'Support\TicketReplyController');
    // Credits / Refunds
    Route::get('/credits-refunds', 'RECController@getCreditRefundCardData');
    // Exports
    Route::get('/exports/financials/descriptive', 'FinancialsController@exportDescriptive');
    Route::get('/exports/financials/diagnostic', 'FinancialsController@exportDiagnostic');
    Route::get('/exports/financials/predictive', 'FinancialsController@exportPredictive');
    Route::get('/exports/productivity/descriptive', 'ProductivityController@exportDescriptive');
    Route::get('/exports/productivity/diagnostic', 'ProductivityController@exportDiagnostic');
    Route::get('/exports/productivity/predictive', 'ProductivityController@exportPredictive');
    Route::get('/exports/ar/manager', 'ARController@exportManager');
    Route::get('/exports/ar/current', 'ARController@exportCurrent');
    Route::get('/exports/ar/late', 'ARController@exportLate');
    Route::get('/exports/ar/delinquent', 'ARController@exportDelinquent');
    Route::get('/exports/ar/collection', 'ARController@exportCollection');
    Route::get('/exports/matter-tracker/manager', 'MatterController@exportManager');
    Route::get('/exports/matter-tracker/red', 'MatterController@exportRed');
    Route::get('/exports/matter-tracker/yellow', 'MatterController@exportYellow');
    Route::get('/exports/matter-tracker/green', 'MatterController@exportGreen');
    Route::get('/matters/new-matters-new', 'MatterController@new_matters_new');
    // Promotion
    Route::resource('/promotion', 'PromotionController');
});
