<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PDFController;
//use App\Http\Controllers\HomeController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\TestDetailsController;
use App\Http\Controllers\KeywordsController;
use App\Http\Controllers\ProfilesController;
use App\Http\Controllers\CommentsController;
use App\Http\Controllers\LabLocationsController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\DrugInteractionController;
use App\Http\Controllers\ConditionController;
use App\Http\Controllers\FdaController;
use App\Http\Controllers\GeneratePDFReportController;
use App\Http\Controllers\TrackOrderController;
use App\Http\Controllers\MetaboliteController;
use App\Http\Controllers\RxCUIController;
use App\Http\Controllers\TradesController;
use App\Http\Controllers\TradesFilterController;



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

Route::get('/', [UsersController::class, 'login'])->name('loginPage');
Auth::routes();

Route::group(['middleware' => ['auth']], function() {
    Route::get('/trackOrders', [TrackOrderController::class, 'index'])->name('home');
/*    Route::post('export-data', [HomeController::class, 'exportExcel']);
    Route::post('export-data-by-patients', [HomeController::class, 'exportUniqueData']);
    Route::post('export-report', [HomeController::class, 'exportDDIReport']);
    Route::post('export-detailed-ddi-report', [HomeController::class, 'exportDetailedDDIReport']);
    Route::post('export-ci-report', [HomeController::class, 'exportCIReport']);
    
    Route::post('fetch-clinics', [HomeController::class, 'fetchClinic']);
    Route::post('fetch-providers', [HomeController::class, 'fetchProvider']);
    Route::post('fetch-patients', [HomeController::class, 'fetchPatient']);*/
    
    Route::get('changePassword',[ChangePasswordController::class,'index']);
    Route::post('update-password', [ChangePasswordController::class, 'updatePassword']);
    
    Route::get('drug-interactions',[DrugInteractionController::class,'index']);
    Route::get('edit-result/{id}',[DrugInteractionController::class,'edit']);
    Route::post('update-result/{id}',[DrugInteractionController::class,'update']);

    Route::get('conditions',[ConditionController::class,'index']);
    Route::get('edit-details/{id}',[ConditionController::class,'edit']);
    Route::post('update-details/{id}',[ConditionController::class,'update']);

    Route::get('trackOrders',[TrackOrderController::class,'index']);
    Route::post('track-orders',[TrackOrderController::class,'exportTrackingReportCsv']);

    Route::get('roles',[RolesController::class,'index']);
    Route::post('insert-role',[RolesController::class,'create']);
    Route::get('edit-role/{id}',[RolesController::class,'edit']);
    Route::post('update-role/{id}',[RolesController::class,'update']);
    Route::delete('delete-role',[RolesController::class,'destroy']);

    Route::resource('users', UsersController::class);
    Route::post('insert-user',[UsersController::class,'create']);
    Route::delete('delete-user',[UsersController::class,'destroy']);
    Route::get('edit/{id}',[UsersController::class,'edit']);
    Route::post('update-user/{id}',[UsersController::class,'update']);
    
    Route::get('testDetails',[TestDetailsController::class,'index']);
    Route::post('insert-testDetails',[TestDetailsController::class,'create']);
    Route::get('edit-testDetails/{id}',[TestDetailsController::class,'edit']);
    Route::post('update-testDetails/{id}',[TestDetailsController::class,'update']);
    Route::delete('delete-testDetails',[TestDetailsController::class,'destroy']);

    Route::get('labLocations',[LabLocationsController::class,'index']);
    Route::get('edit-location/{id}',[LabLocationsController::class,'edit']);
    Route::post('update-location/{id}',[LabLocationsController::class,'update']);
    Route::post('insert-labLocation',[LabLocationsController::class,'create']);
    Route::delete('delete-location',[LabLocationsController::class,'destroy']);

    Route::get('keywords',[KeywordsController::class,'index']);
    Route::post('insert-keyword',[KeywordsController::class,'create']);
    Route::get('edit-keyword/{id}',[KeywordsController::class,'edit']);
    Route::post('update-keyword/{id}',[KeywordsController::class,'update']);
    Route::delete('delete-keyword',[KeywordsController::class,'destroy']);

    Route::get('profiles',[ProfilesController::class,'index']);
    Route::get('edit-profile/{id}',[ProfilesController::class,'edit']);
    Route::post('update-profile/{id}',[ProfilesController::class,'update']);
    Route::delete('delete-profile',[ProfilesController::class,'destroy']);
    Route::post('insert-profile',[ProfilesController::class,'create']);

    Route::get('comments',[CommentsController::class,'index'])->name('comments');
    Route::post('insert-comment',[CommentsController::class,'create']);
    Route::get('edit-comment/{id}',[CommentsController::class,'edit']);
    Route::post('update-comment/{id}',[CommentsController::class,'update']);
    Route::delete('delete-comment',[CommentsController::class,'destroy']);

    Route::get('metabolites', [MetaboliteController::class, 'index']);
    Route::post('insert-metabolites', [MetaboliteController::class, 'create']);
    Route::get('edit-metabolites/{id}', [MetaboliteController::class, 'edit']);
    Route::post('update-metabolites/{id}', [MetaboliteController::class, 'update']);
    Route::delete('delete-metabolites', [MetaboliteController::class, 'destroy']);

    Route::get('rxcui', [RxCUIController::class, 'index']);
    Route::post('insert-rxcui', [RxCUIController::class, 'create']);
    Route::get('edit-rxcui/{id}', [RxCUIController::class, 'edit']);
    Route::post('update-rxcui/{id}', [RxCUIController::class, 'update']);
    Route::delete('delete-rxcui', [RxCUIController::class, 'destroy']);

    Route::get('trades', [TradesController::class, 'index']);
    Route::post('insert-trades', [TradesController::class, 'create']);
    Route::get('edit-trades/{id}', [TradesController::class, 'edit']);
    Route::post('update-trades/{id}', [TradesController::class, 'update']);
    Route::delete('delete-trades', [TradesController::class, 'destroy']);

    // failed_order_pdf_Route

    Route::get('/trades_filter', [TradesFilterController::class, 'index'])->name('trades');
    Route::delete('failed-Order/delete', [TradesFilterController::class, 'destroy'])->name('delete.failed.orders');
    // Route::get('/create-trades', [TradesFilterController::class, 'create'])->name('trades.create');
    // Route::post('/store-trades', [TradesFilterController::class, 'store'])->name('trades.store');
    // Route::get('/trades/{tradesFilter}/edit', [TradesFilterController::class, 'edit'])->name('trades.edit');

    //** Test Purpose */
    Route::get('/report-pdf/{show_log}/{order_code}', [GeneratePDFReportController::class, 'PDFReport_Get_Live']); 
    // http://127.0.0.1:8000/report-pdf/0/235740-96fdd707-133c-4a08-ae4a-43f0a18def54
     Route::get('/ack_stratus/{show_log}/{order_code}', [GeneratePDFReportController::class, 'test_Stratus_ack']); 
     http://127.0.0.1:8000/ack_stratus/1/233549-5c7f2a87-3ac8-47a9-bee0-9c6dabbbdeaa

});

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    // Route::get('/generate-pdf', [PDFController::class, 'generatePDF']);
    // Route::get('/generate-pdf2', [GeneratePDFReportController::class, 'ShowPDFReport_InView']);
    // Route::get('/generate-pdf3', [GeneratePDFReportController::class, 'ShowPDFReport_InViewForStratus']);
    // Route::POST('/generate-pdf3_live', [GeneratePDFReportController::class, 'ShowPDFReport_InViewForStratus_Live']);
    Route::get('{testName}',[FdaController::class,'getFdaTestDetails']);



