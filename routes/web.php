<?php
  
use Illuminate\Support\Facades\Route;
  
use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Staff\HolidayController;
use App\Http\Controllers\Manager\ManagerController;
use App\Http\Controllers\Manager\ServiceController;
use App\Http\Controllers\Staff\StaffServiceController;
use App\Http\Controllers\Frontend\FrontendController;
  
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

// Frontend

// Homepage
Route::get('/', [FrontendController::class, 'index'])->name('frontend.index');

// Contact
Route::get('/contact', [FrontendController::class, 'contact'])->name('frontend.contact');

//Base login
Route::get('/dashboard', [HomeController::class, 'index'])->name('home');

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

    // Reassign staff by manager
    Route::post('/manager/update-sub-service-staff', [ServiceController::class, 'updateSubServiceStaff'])->name('manager.updateSubServiceStaff');

    Route::post('/manager/change-sub-service-status', [ServiceController::class,'changeSubServiceStatus']);

    // Start, Stop, Start Break, Stop Break by Manager
    Route::post('/manager/start-work-time', [ServiceController::class,'startWorkTime']);
    Route::post('/manager/stop-work-time', [ServiceController::class,'stopWorkTime']);
    Route::post('/manager/start-break', [ServiceController::class,'startBreak']);
    Route::post('/manager/stop-break', [ServiceController::class,'stopBreak']);

    // Manager holiday
    Route::get('/manager/holiday', [HolidayController::class, 'indexManager'])->name('manager.holiday');
    Route::post('/manager/holiday-request', [HolidayController::class, 'holidayRequestManager'])->name('manager.holidayRequest');

    // work time check
    Route::get('/manager/check-work-time-status', [ServiceController::class,'checkWorkTimeStatus']);

     //  Take Break and Break Out 
     Route::post('/manager/take-break',  [ServiceController::class,'takeBreak']);
     Route::get('/manager/check-break-status',  [ServiceController::class,'checkBreakStatus']);
     Route::post('/manager/break-out', [ServiceController::class,'breakOut']);

     //  Data showing in modal
     Route::get('/manager/get-completed-services-modal', [ServiceController::class, 'getCompetedServicesModal']);

     // Note and additional work
     Route::post('/manager/save-notes', [ServiceController::class, 'saveNotes'])->name('saveNotes');

     //  Client list
    Route::get('/manager/client', [ClientController::class, 'indexManager'])->name('allClientManager');
    Route::get('/manager/client-list', [ClientController::class, 'getClientsManager'])->name('get.Clients.manager');

    // Task list
    Route::get('/manager/task-list', [ServiceController::class, 'allTaskList'])->name('allTaskListManager');
    
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

     // Change Status of completed sub service 
    Route::post('/staff/change-sub-service-status', [StaffServiceController::class,'changeSubServiceStatus']);
    
    // Update Status of assigned sub service 
    Route::post('/staff/update-sub-service-status', [StaffServiceController::class,'updateSubServiceStatus']);

    // Start, Stop, Start Break, Stop Break by Staff
    Route::post('/staff/start-work-time', [StaffServiceController::class,'startWorkTime']);
    Route::post('/staff/stop-work-time', [StaffServiceController::class,'stopWorkTime']);
    Route::post('/staff/start-break', [StaffServiceController::class,'startBreak']);
    Route::post('/staff/stop-break', [StaffServiceController::class,'stopBreak']);

     // staff holiday
     Route::get('/staff/holiday', [HolidayController::class, 'index'])->name('staff.holiday');
     Route::post('/staff/holiday-request', [HolidayController::class, 'holidayRequest'])->name('staff.holidayRequest');

     Route::get('/staff/check-work-time-status', [StaffServiceController::class,'checkWorkTimeStatus']);

     //  Take Break and Break Out 
     Route::post('/staff/take-break',  [StaffServiceController::class,'takeBreak']);
     Route::get('/staff/check-break-status',  [StaffServiceController::class,'checkBreakStatus']);
     Route::post('/staff/break-out', [StaffServiceController::class,'breakOut']);

     //  Data showing in modal
     Route::get('/staff/get-completed-services-modal', [StaffServiceController::class, 'getCompetedServicesModal']);

     // Note and additional work
     Route::post('/staff/save-notes', [StaffServiceController::class, 'saveNotes'])->name('saveNotes');

    //  Client list
    Route::get('/staff/client', [ClientController::class, 'indexStaff'])->name('allClientStaff');
    Route::get('/staff/client-list', [ClientController::class, 'getClientsStaff'])->name('get.Clients.staff');

    // Task list
    Route::get('/staff/task-list', [StaffServiceController::class, 'allTaskList'])->name('allTaskList');

    //Idle time count 
    Route::post('/staff-idle-time', [StaffServiceController::class, 'logIdleTime'])->name('staff.idle.time');
    
});

//User
Route::middleware(['auth', 'user-access:user'])->group(function () {

    Route::get('user/home', [HomeController::class, 'userHome'])->name('user.home');

});