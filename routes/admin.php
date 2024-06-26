<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\HolidayController;
use App\Http\Controllers\Admin\ManagerController;
use App\Http\Controllers\Admin\ProrotaController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ClientTypeController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\SubServiceController;
use App\Http\Controllers\Admin\WebServiceController;
use App\Http\Controllers\Admin\ContactInfoController;
use App\Http\Controllers\Admin\HolidayTypeController;
use App\Http\Controllers\Admin\BusinessInfoController;
use App\Http\Controllers\Admin\DirectorInfoController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\WeWorkWithImageController;

//Fallback route
Route::fallback(function () {
    return redirect('/');
});

//Admin
Route::middleware(['auth', 'user-access:admin'])->prefix('admin')->group(function () {

    Route::get('/home', [HomeController::class, 'adminHome'])->name('admin.home');

    //Admin crud
    Route::get('/new-admin', [AdminController::class, 'getAdmin'])->name('allAdmin');
    Route::post('/new-admin', [AdminController::class, 'adminStore']);
    Route::get('/new-admin/{id}/edit', [AdminController::class, 'adminEdit']);
    Route::post('/new-admin-update', [AdminController::class, 'adminUpdate']);
    Route::get('/new-admin/{id}', [AdminController::class, 'adminDelete']);

    //Manager crud
    Route::get('/manager', [ManagerController::class, 'index'])->name('allManager');
    Route::post('/manager', [ManagerController::class, 'store']);
    Route::get('/manager/{id}/edit', [ManagerController::class, 'edit']);
    Route::post('/manager-update', [ManagerController::class, 'update']);
    Route::get('/manager/{id}', [ManagerController::class, 'delete']);

    //Staff crud
    Route::get('/staff', [StaffController::class, 'index'])->name('allStaff');
    Route::get('/staff-list', [StaffController::class, 'getStuffs'])->name('get.Stuffs');
    Route::get('/create-staff', [StaffController::class, 'create'])->name('createStaff');
    Route::post('/staff', [StaffController::class, 'store']);
    Route::get('/staff/{id}/edit', [StaffController::class, 'edit']);
    Route::post('/staff-update', [StaffController::class, 'update']);
    Route::get('/staff/{id}', [StaffController::class, 'delete']);
    
    //Staff Delete
    Route::delete('/delete-staff/{id}', [StaffController::class, 'deleteStaff'])->name('delete.staff');

    //Staff status change
    Route::post('/staff-change-status', [StaffController::class,'changeStatus'])->name('staff.change.status');

    //Each staff details
    Route::get('/staff/details/{id}', [StaffController::class,'showDetails'])->name('staff.details');

    // Update staff details
    Route::post('/staff/{id}', [StaffController::class,'updateStaff'])->name('staff.update');

    //Get staff details
    Route::get('/get-staff-details/{id}', [StaffController::class,'getStaffDetails'])->name('get.staff.details');

    // Update staff's personal and job Information
    Route::post('/staff-personal-update', [StaffController::class,'updateStaffPersonal'])->name('staff.update.persoanl');
    Route::post('/staff-job-update', [StaffController::class,'updateStaffJob'])->name('staff.update.job');

    // completed, in progress, due tasks list
    Route::get('/completed-tasks', [ServiceController::class,'completedTasks'])->name('completed.tasks');
    Route::get('/tasks-in-progress',  [ServiceController::class,'tasksInProgress'])->name('tasks.in_progress');
    Route::get('/due-tasks',  [ServiceController::class,'dueTasks'])->name('due.tasks');

    // Holiday's list and edit holiday
    Route::get('/holidays', [HolidayController::class,'getHolidaysForStaff']);
    Route::post('/holidays/update', [HolidayController::class,'updateDay']);

    // Staff log out by admin
    Route::post('/staff-logout/{attendenceId}', [HomeController::class, 'customlogout']);

    //Department crud
    Route::get('/department', [DepartmentController::class, 'index'])->name('allDepartment');
    Route::post('/department', [DepartmentController::class, 'store']);
    Route::get('/department/{id}/edit', [DepartmentController::class, 'edit']);
    Route::post('/department-update', [DepartmentController::class, 'update']);
    Route::get('/department/{id}', [DepartmentController::class, 'delete']);

    //Clients crud
    Route::get('/client', [ClientController::class, 'index'])->name('allClient');
    Route::get('/client-list', [ClientController::class, 'getClients'])->name('get.Clients');
    Route::get('/create-client', [ClientController::class, 'create'])->name('createClient');
    Route::get('/create-client/{id}', [ClientController::class, 'create1'])->name('createNewClient');
    Route::post('/client', [ClientController::class, 'store']);
    Route::get('/client/{id}/edit', [ClientController::class, 'edit']);
    Route::post('/client-update', [ClientController::class, 'update']);
    Route::get('/client/{id}', [ClientController::class, 'delete']);

    //Client Delete
    Route::delete('/delete-client/{id}', [ClientController::class, 'deleteContact'])->name('delete.client');

    //Client status change
    Route::post('/client-change-status', [ClientController::class,'changeStatus'])->name('client.change.status');

    //client update form
    Route::get('/client/update-form/{id}', [ClientController::class,'updateForm'])->name('client.update.form');

    //Client details update
    Route::post('/client-details-update/{id}', [ClientController::class,'updateClientDetails']);

    //Client businessinfo update
    Route::post('/client-businessinfo-update/{id}', [ClientController::class,'updateClientBusinessInfo']);

    //Client directorinfo update
    Route::post('/client-directorinfo-update/{id}', [ClientController::class,'updateClientDirectorInfo']);

    //Director info delete
    Route::delete('/delete-director/{id}', [ClientController::class, 'destroyDirector']);

    //Client contactinfo update
    Route::post('/client-contactinfo-update/{id}', [ClientController::class,'updateClientContactInfo']);

    //Contact info delete
    Route::delete('/delete-contact/{id}', [ClientController::class, 'destroyContact']);

    // Fetch sssigned and non assigned services together
    Route::get('/client-services/{clientId}', [ClientController::class, 'getClientServices']);

    //Contactinfo update
    Route::post('/client-contactinfo-update/{id}', [ClientController::class,'updateClientContactInfo']);

    //Clinet assigned services update
    Route::post('/client-services-update/{id}', [ClientController::class,'updateClientServices']);

    //Contact info crud
    Route::get('/contact-info', [ContactInfoController::class, 'index'])->name('allContactInfo');
    Route::post('/contact-info', [ContactInfoController::class, 'store']);
    Route::get('/contact-info/{id}/edit', [ContactInfoController::class, 'edit']);
    Route::post('/contact-info-update', [ContactInfoController::class, 'update']);
    Route::get('/contact-info/{id}', [ContactInfoController::class, 'delete']);

    //Fetch all services
     Route::get('/all-services', [ServiceController::class, 'getAllServices']);

     //Create specific service
     Route::post('/create-specific-service', [ServiceController::class, 'createSpecificService']);

    // Service assign to client
     Route::post('/service-assign', [ServiceController::class, 'serviceAssign']);

    //Fetch all assigned service
    Route::get('/get-all-services', [ServiceController::class, 'getAllAssignedServices']);

     //Fetch assigned services
     Route::get('/get-assigned-services/{client_id}', [ServiceController::class, 'getAssignedServices']);

     //Store fetched services + staff
     Route::post('/assign-service-staff', [ServiceController::class, 'assignServiceStaff']);

    //  Fetch assigned services + staff 
     Route::get('/get-services-client-staff', [ServiceController::class, 'getServicesClientStaff']);

     //  Fetch completed services
     Route::get('/get-completed-services', [ServiceController::class, 'getCompletedServices']);

     //  Fetch assigned services
     Route::get('/get-assigned-service', [ServiceController::class, 'getAssignedService']);

     //  Fetch todays deadline services
     Route::get('/get-todays-deadline-service', [ServiceController::class, 'getTodaysDeadlineService']);
     
     // Fetch Sub Services
     Route::get('/getClientSubService/{clientserviceId}', [ServiceController::class, 'getClientSubService']);

     //Change status of assigned services + staff
     Route::post('/update-service-status', [ServiceController::class, 'updateServiceStatus'])->name('update-service-status');

    //  Fetch sub services under one service
    Route::get('/getSubServices/{serviceId}', [ServiceController::class, 'getSubServices'])->name('getSubServices');

    //Store service + sub service + assign to staff
    Route::post('/store-service', [ServiceController::class,'saveService'])->name('saveService');

    //Update service + sub service + assign to staff
    Route::post('/update-service', [ServiceController::class,'updateService'])->name('updateService');

    //Update service + sub service + assign to staff from Dashboard
    Route::post('/update-service-staff', [ServiceController::class,'updateStaffService']);

    Route::delete('/delete-sub-service/{id}', [ServiceController::class, 'deleteSubservice']);

    // Fetch Sub Services
    Route::get('/getClientSubServices/{clientserviceId}', [ServiceController::class, 'getClientSubServices']);

    //Update sub service from dashboard
    Route::post('/update-sub-services', [ServiceController::class,'updateSubservices'])->name('updateSubServices');

    //Business info crud
    Route::get('/business-info', [BusinessInfoController::class, 'index'])->name('allBusinessInfo');
    Route::get('/create-business-info', [BusinessInfoController::class, 'create'])->name('createBusinessInfo');
    Route::post('/business-info', [BusinessInfoController::class, 'store']);
    Route::get('/business-info/{id}/edit', [BusinessInfoController::class, 'edit']);
    Route::post('/business-info-update', [BusinessInfoController::class, 'update']);
    Route::get('/business-info/{id}', [BusinessInfoController::class, 'delete']);

    //Director info crud
    Route::get('/director-info', [DirectorInfoController::class, 'index'])->name('allDirectorInfo');
    Route::post('/director-info', [DirectorInfoController::class, 'store']);
    Route::get('/director-info/{id}/edit', [DirectorInfoController::class, 'edit']);
    Route::post('/director-info-update', [DirectorInfoController::class, 'update']);
    Route::get('/director-info/{id}', [DirectorInfoController::class, 'delete']);

    //Client types crud
    Route::get('/client-type', [ClientTypeController::class, 'index'])->name('allClientType');
    Route::post('/client-type', [ClientTypeController::class, 'store']);
    Route::get('/client-type/{id}/edit', [ClientTypeController::class, 'edit']);
    Route::post('/client-type-update', [ClientTypeController::class, 'update']);
    Route::get('/client-type/{id}', [ClientTypeController::class, 'delete']);

    //Services crud
    Route::get('/service', [ServiceController::class, 'index'])->name('allService');
    Route::post('/service', [ServiceController::class, 'store']);
    Route::get('/service/{id}/edit', [ServiceController::class, 'edit']);
    Route::post('/service-update', [ServiceController::class, 'update']);
    Route::get('/service/{id}', [ServiceController::class, 'delete']);

    //SubServices crud
    Route::post('/sub-service', [SubServiceController::class, 'store']);

    //Web Services crud
    Route::get('/web-service', [WebServiceController::class, 'index'])->name('allWebService');
    Route::post('/web-service', [WebServiceController::class, 'store']);
    Route::get('/web-service/{id}/edit', [WebServiceController::class, 'edit']);
    Route::post('/web-service-update', [WebServiceController::class, 'update']);
    Route::get('/web-service/{id}', [WebServiceController::class, 'delete']);

    //Contact message
    Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('allContactMessage');

    //Img: we work with crud
    Route::get('/we-work-image', [WeWorkWithImageController::class, 'index'])->name('weWorkImage');
    Route::post('/we-work-image', [WeWorkWithImageController::class, 'store']);
    Route::get('/we-work-image/{id}/edit', [WeWorkWithImageController::class, 'edit']);
    Route::post('/we-work-image-update', [WeWorkWithImageController::class, 'update']);
    Route::get('/we-work-image/{id}', [WeWorkWithImageController::class, 'delete']);

    //Previously logged in staffs
    Route::get('/attendence-records', [StaffController::class, 'prevLogStaffs'])->name('prevLogStaffs');

    Route::get('/all-attendence-records', [StaffController::class, 'allPrevLogStaffs'])->name('allPrevLogStaffs');

    Route::post('/prev-staffs/update/{id}', [StaffController::class, 'updateLogs']);

    // Today's staff's task details and edit by admin
    Route::get('/task-details/{user_id}', [StaffController::class, 'staffTaskDetails'])->name('task.details.staff');

    Route::post('/update-client-service', [StaffController::class, 'updateClientService']);

    // Change Status of sub service
    Route::post('/update-sub-service-status', [ServiceController::class,'updateSubServiceStatus']);

    // prorota make
    Route::get('/prorota', [ProrotaController::class, 'index'])->name('prorota');
    Route::get('/prorota-list', [ProrotaController::class, 'getprorota'])->name('get.prorota');
    Route::get('/create-prorota', [ProrotaController::class, 'create'])->name('createprorota');
    Route::post('/prorota', [ProrotaController::class, 'store']);
    Route::post('/prorota/update', [ProrotaController::class, 'update']);
    Route::get('/prorota/details/{id}', [ProrotaController::class,'showDetails'])->name('prorota.details');
    Route::delete('/delete-prorota/{id}', [ProrotaController::class, 'deleteData'])->name('delete.staff');
    Route::get('/prorota/edit/{id}', [ProrotaController::class,'edit'])->name('prorota.edit');

    
    // holiday make
    Route::get('/holiday', [HolidayController::class, 'index'])->name('holiday');
    Route::get('/holiday-list', [HolidayController::class, 'getholiday'])->name('get.holiday');
    Route::get('/create-holiday', [HolidayController::class, 'create'])->name('createholiday');
    Route::post('/holiday', [HolidayController::class, 'store']);
    Route::post('/holiday/update', [HolidayController::class, 'update']);
    Route::get('/holiday/details/{id}', [HolidayController::class,'showDetails'])->name('holiday.details');
    Route::delete('/delete-holiday/{id}', [HolidayController::class, 'deleteData'])->name('delete.staff');
    Route::get('/holiday/edit/{id}', [HolidayController::class,'edit'])->name('holiday.edit');
    Route::post('/store-holiday', [HolidayController::class, 'storeHoliday'])->name('store.holiday');
    Route::get('/edit-holiday/{id}', [HolidayController::class,'editHoliday'])->name('editHoliday');
    Route::post('/holiday-update/{id}', [HolidayController::class,'updateHoliday']);

    // get holiday type
    Route::post('/get-holiday-type', [HolidayController::class, 'getHolidayType'])->name('get.holiday.type');

    //Holiday Report
    Route::get('/holiday-report', [HolidayController::class, 'holidayReport'])->name('holidayReport');
    Route::post('/get-holiday-data', [HolidayController::class, 'getHolidayData'])->name('getHolidayData');

    // User absent log
    Route::post('/add-comment', [LogController::class, 'addComment'])->name('add.comment');

    //holiday types
    Route::get('/holiday-type', [HolidayTypeController::class, 'index'])->name('allHolidayType');
    Route::post('/holiday-type', [HolidayTypeController::class, 'store']);
    Route::get('/holiday-type/{id}/edit', [HolidayTypeController::class, 'edit']);
    Route::post('/holiday-type-update', [HolidayTypeController::class, 'update']);
    Route::get('/holiday-type/{id}', [HolidayTypeController::class, 'delete']);


});