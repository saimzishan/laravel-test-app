
<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/ppoath', function () {
    phpinfo();
});
Route::get('/mail', "AuthController@test");
Route::get('/login', 'AuthController@login')->name("login");
Route::post('/login', 'AuthController@doLogin');
Route::get('/logout', 'AuthController@logout')->name("logout");
Route::get('/register', 'AuthController@register')->name("register");
Route::post('/register', 'AuthController@doRegister');
Route::get('/dummy/sendData', 'PostController@index');
Route::get('/dummy/send/{id}', 'PostController@sendDummy');
Route::get('/dummy/sendcl/{id}', 'PostController@sendtoDummyClio');
Route::get('/dummy/dummycl/{id}', 'PostController@changeNameDummyClio');
Route::get('/dummy/syncPP', 'PostController@sendPPToCLDummy');
Route::get('/test1', 'TestController@test');
Route::get('/clientwrittenoff', 'ClientManagementController@getClientWrittenoff');
Route::get('/employeewrittenoff', 'KPIController@getEmployeeWrittenoff');


// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset')->name('password.update');

// Email Verification Routes...
Route::get('email/verify', 'Auth\VerificationController@show')->name('verification.notice');
Route::get('email/verify/{id}', 'Auth\VerificationController@verify')->name('verification.verify');
Route::get('email/resend', 'Auth\VerificationController@resend')->name('verification.resend');

// Report Routes
Route::get('table/reports','ReportsController@generateMonthlyTableReports');
// User Reports

Route::get('/dev/migrate', "DevController@migrate");
Route::post('/payments/stripe/webhook', 'Stripe\WebHookController@handleWebhook');
Route::group(["middleware"=>"CustomAuth"], function(){
    Route::get('users/reports','ReportsController@getUserProductivity');
    Route::get('/reports/{month}','ReportsController@generateMonthlyReports');
    Route::get('/oauth/pp', 'FirmIntegrationController@auth');
    Route::get('/oauth/clio', 'FirmIntegrationController@authClio');
    Route::get('/oauth/quickbooks', 'FirmIntegrationController@authQuickbooks');
//    Route::get('/payments/stripe', 'Stripe\PaymentController@create');
    Route::post('/payments/stripe', 'Stripe\PaymentController@store');
//    Route::get('/payments/stripe/change', 'Stripe\ChangeCardController@create');
    Route::post('/payments/stripe/change', 'Stripe\ChangeCardController@store');
    Route::post('/payments/stripe/re-activate', 'Stripe\ReActivationController@store');
    Route::get('/{any}', 'SpaController@pages')->where('any', '.*');
});
