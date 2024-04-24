<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ManagerController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ClientTypeController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\SubServiceController;
use App\Http\Controllers\Admin\WebServiceController;
use App\Http\Controllers\Admin\ContactInfoController;
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

    // Admin logs out staff
    Route::post('/custom-logout-admin/{userId}', [HomeController::class, 'sessionClearByAdmin'])->name('customLogoutByAdmin');

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
    Route::get('/create-staff', [StaffController::class, 'create'])->name('createStuff');
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

     //Change status of assigned services + staff
     Route::post('/update-service-status', [ServiceController::class, 'updateServiceStatus'])->name('update-service-status');

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
    Route::get('/prev-staffs', [StaffController::class, 'prevLogStaffs'])->name('prevLogStaffs');

});