<?php
  
use Illuminate\Support\Facades\Route;
  
use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Manager\ManagerController;
use App\Http\Controllers\Manager\ServiceController;
  
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

    Route::get('/manager/get-all-services', [ServiceController::class, 'getAllAssignedServices']);

    // Fetch Sub Services
    Route::get('/manager/getClientSubServices/{clientserviceId}', [ServiceController::class, 'getClientSubServices']);

    // Change Status of sub service
    Route::post('/manager/update-sub-service-status', [ServiceController::class,'updateSubServiceStatus']);

    //Store message to staff
    Route::post('/manager/store-message', [ServiceController::class,'storeMessage']);
    
});

//Staff
Route::middleware(['auth', 'user-access:staff'])->group(function () {

    Route::get('/staff/home', [HomeController::class, 'staffHome'])->name('staff.home');

    // Staff profile and profile update
    Route::get('/staff/profile/edit', [StaffController::class, 'editProfile'])->name('staff.profile.edit');
    Route::post('/staff/profile', [StaffController::class, 'updateProfile']);

    //  Fetch assigned services + staff 
     Route::get('/staff/get-services-client-staff', [ServiceController::class, 'getServicesClientStaff']);

     //Change status of assigned services + staff
     Route::post('/staff/update-service-status', [ServiceController::class, 'updateServiceStatus'])->name('update-service-status');

     //Custom logout
     Route::post('/custom-logout', [HomeController::class, 'sessionClear'])->name('customLogout');
    
});

//User
Route::middleware(['auth', 'user-access:user'])->group(function () {

    Route::get('user/home', [HomeController::class, 'userHome'])->name('user.home');

});