<?php
  
use Illuminate\Support\Facades\Route;
  
use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Staff\HolidayController;
use App\Http\Controllers\Manager\ManagerController;
use App\Http\Controllers\Manager\ServiceController;
use App\Http\Controllers\Staff\StaffServiceController;
  
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/clear', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    return "Cleared!";
});

//Fallback route
Route::fallback(function () {
    return redirect('/');
});
  
Auth::routes();

//Base login
Route::get('/', [HomeController::class, 'index'])->name('home');

//Manager
Route::middleware(['auth', 'user-access:manager'])->group(function () {

    Route::get('/manager/home', [HomeController::class, 'managerHome'])->name('manager.home');

    // Staff profile and profile update
    Route::get('/manager/profile/edit', [ManagerController::class, 'editProfile'])->name('manager.profile.edit');
    Route::post('/manager/profile', [ManagerController::class, 'updateProfile']);

    // Fetch all services by manager
    Route::get('/manager/get-all-services', [ServiceController::class, 'getAllAssignedServices']);

    // Fetch completed services by manager
    Route::get('/manager/get-completed-services', [ServiceController::class, 'getCompetedServices']);

    // Fetch Sub Services
    Route::get('/manager/getClientSubServices/{clientserviceId}', [ServiceController::class, 'getClientSubServices']);

    // Fetch Service Mesaage
    Route::get('/manager/getServiceMessage/{clientSubServiceId}', [ServiceController::class, 'getServiceMessages']);

    // Change Status of sub service
    Route::post('/manager/update-sub-service-status', [ServiceController::class,'updateSubServiceStatus']);

    //Store message to staff
    Route::post('/manager/store-message', [ServiceController::class,'storeMessage']);

    // Start, Stop, Start Break, Stop Break by Manager
    Route::post('/manager/start-work-time', [ServiceController::class,'startWorkTime']);
    Route::post('/manager/stop-work-time', [ServiceController::class,'stopWorkTime']);
    Route::post('/manager/start-break', [ServiceController::class,'startBreak']);
    Route::post('/manager/stop-break', [ServiceController::class,'stopBreak']);

    // Manager holiday
    Route::get('/manager/holiday', [HolidayController::class, 'indexManager'])->name('manager.holiday');
    Route::post('/manager/holiday-request', [HolidayController::class, 'holidayRequestManager'])->name('manager.holidayRequest');
});

//Staff
Route::middleware(['auth', 'user-access:staff'])->group(function () {

    Route::get('/staff/home', [HomeController::class, 'staffHome'])->name('staff.home');

    // Staff profile and profile update
    Route::get('/staff/profile/edit', [StaffController::class, 'editProfile'])->name('staff.profile.edit');
    Route::post('/staff/profile', [StaffController::class, 'updateProfile']);

    //  Task list
     Route::get('/staff/get-all-services', [StaffServiceController::class, 'getServicesClientStaff']);

     //  completed list
     Route::get('/staff/get-completed-services', [StaffServiceController::class, 'getCompetedServices']);

     // Fetch Sub Services
     Route::get('/staff/getClientSubServices/{clientserviceId}', [StaffServiceController::class, 'getClientSubServices']);

     // Fetch Service Mesaage
    Route::get('/staff/getServiceMessage/{clientSubServiceId}', [StaffServiceController::class, 'getServiceMessages']);

    //Store message to staff
    Route::post('/staff/store-message', [StaffServiceController::class,'storeMessage']);

     // Change Status of sub service
    Route::post('/staff/update-sub-service-status', [StaffServiceController::class,'updateSubServiceStatus']);

    // Start, Stop, Start Break, Stop Break by Staff
    Route::post('/staff/start-work-time', [StaffServiceController::class,'startWorkTime']);
    Route::post('/staff/stop-work-time', [StaffServiceController::class,'stopWorkTime']);
    Route::post('/staff/start-break', [StaffServiceController::class,'startBreak']);
    Route::post('/staff/stop-break', [StaffServiceController::class,'stopBreak']);

     //Custom logout
     Route::post('/custom-logout', [HomeController::class, 'sessionClear'])->name('customLogout');

     // staff holiday
     Route::get('/staff/holiday', [HolidayController::class, 'index'])->name('staff.holiday');
     Route::post('/staff/holiday-request', [HolidayController::class, 'holidayRequest'])->name('staff.holidayRequest');

     
    
});

//User
Route::middleware(['auth', 'user-access:user'])->group(function () {

    Route::get('user/home', [HomeController::class, 'userHome'])->name('user.home');

});