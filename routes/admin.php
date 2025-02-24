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
use App\Http\Controllers\Admin\CompanyDetailsController;
use App\Http\Controllers\Admin\SoftCodeController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\HomepageController;
use App\Http\Controllers\Admin\TimeslotController;
use App\Http\Controllers\Admin\ServicepageController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PackageFeatureController;
use App\Http\Controllers\Admin\BusinessServiceController;
use App\Http\Controllers\Admin\BusinessValueController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\CaseStudiesController;
use App\Http\Controllers\Admin\LatestInsightController;
use App\Http\Controllers\Admin\OurTeamController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\CareerController;
use App\Http\Controllers\Admin\FAQController;
use App\Http\Controllers\Admin\GoogleReviewController;
use App\Http\Controllers\Admin\MetaDataController;
use App\Http\Controllers\Admin\ContactMailController;
use App\Http\Controllers\Admin\MailContentController;
use App\Http\Controllers\Admin\OneTimeJobController;
use App\Http\Controllers\Admin\RecentUpdateController;
use App\Http\Controllers\Admin\NoteController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\LogoutController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TrashBinController;

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

    Route::get('/user-activities/{id}', [AdminController::class, 'showUserActivities'])->name('user.activities');
    
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

    Route::get('/department-activities/{id}', [DepartmentController::class, 'showDepartmentActivities'])->name('department.activities');

    //Clients crud
    Route::get('/client', [ClientController::class, 'index'])->name('allClient');
    Route::get('/client-list', [ClientController::class, 'getClients'])->name('get.Clients');
    Route::get('/create-client', [ClientController::class, 'create'])->name('createClient');
    Route::post('/client', [ClientController::class, 'store']);

    Route::get('/client-activities/{id}', [ClientController::class, 'showClientActivities'])->name('client.activities');

    //Client Delete
    Route::delete('/delete-client/{id}', [ClientController::class, 'deleteClient'])->name('delete.client');

    Route::get('/client/report/{id}', [ClientController::class, 'clientReport'])->name('client.report');

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

    //Clinet assigned services update
    Route::post('/client-services-update/{id}', [ClientController::class,'updateClientServices']);

    //Contact info crud
    Route::get('/contact-info', [ContactInfoController::class, 'index'])->name('allContactInfo');
    Route::post('/contact-info', [ContactInfoController::class, 'store']);
    Route::get('/contact-info/{id}/edit', [ContactInfoController::class, 'edit']);
    Route::post('/contact-info-update', [ContactInfoController::class, 'update']);
    Route::get('/contact-info/{id}', [ContactInfoController::class, 'delete']);

    //Recent Update
    Route::post('/recent-updates', [RecentUpdateController::class, 'store'])->name('recent-updates.store');
    Route::post('/recent-updates/{id}', [RecentUpdateController::class, 'update'])->name('recent-updates.update');
    Route::delete('/recent-updates/{id}', [RecentUpdateController::class, 'destroy'])->name('recent-updates.destroy');

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

     //Fetch assigned services
     Route::get('/getServiceMessage/{clientSubServiceId}', [ServiceController::class, 'getServiceMessage']);

     Route::post('/store-message', [ServiceController::class, 'storeMessage']);

    Route::get('/getServiceComment/{clientServiceId}', [ServiceController::class, 'getServiceComment']);
    
    Route::post('/store-comment', [ServiceController::class, 'storeComment']);

     //Store fetched services + staff
     Route::post('/assign-service-staff', [ServiceController::class, 'assignServiceStaff']);

    //  Fetch assigned services + staff 
     Route::get('/get-services-client-staff', [ServiceController::class, 'getServicesClientStaff']);

     //  Fetch completed services
     Route::get('/get-completed-services', [ServiceController::class, 'getCompletedServices']);

     //  Fetch assigned services
     Route::get('/get-assigned-service', [ServiceController::class, 'getAssignedService']);

     Route::post('/client-service-change-status', [ServiceController::class, 'changeServiceStatus']);

    //  Fetch one time  assigned services
    Route::get('/get-one-time-assigned-service', [ServiceController::class, 'getOneTimeAssignedService']);

    //  Fetch one time completed services
    Route::get('/get-one-time-completed-service', [ServiceController::class, 'getOneTimeCompletedService']);

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

    Route::get('/client-type-activities/{id}', [ClientTypeController::class, 'showClientTypeActivities'])->name('client.type.activities');

    //Services crud
    Route::get('/service', [ServiceController::class, 'index'])->name('allService');
    Route::post('/service', [ServiceController::class, 'store']);
    Route::get('/service/{id}/edit', [ServiceController::class, 'edit']);
    Route::post('/service-update', [ServiceController::class, 'update']);
    Route::get('/service/{id}', [ServiceController::class, 'delete']);

    Route::get('/service-activity/{id}', [ServiceController::class, 'showActivity'])->name('service.activity');

    Route::post('/check-sub-service-assignment', [ServiceController::class, 'checkAssignment']);

    //SubServices crud
    Route::post('/sub-service', [SubServiceController::class, 'store']);

    Route::post('/toggle-continuous', [ServiceController::class, 'toggleContinuous'])->name('toggle.continuous');

    //Web Services crud
    Route::get('/web-service', [WebServiceController::class, 'index'])->name('allWebService');
    Route::post('/web-service', [WebServiceController::class, 'store']);
    Route::get('/web-service/{id}/edit', [WebServiceController::class, 'edit']);
    Route::post('/web-service-update', [WebServiceController::class, 'update']);
    Route::get('/web-service/{id}', [WebServiceController::class, 'delete']);

    //Contact message
    Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('allContactMessage');
    Route::get('/contact-messages/{id}', [ContactMessageController::class, 'delete']);

    //web contact page
    Route::get('/web-contact', [ContactMessageController::class, 'webContact'])->name('webContact');
    Route::put('/web-contact/update', [ContactMessageController::class, 'webContactUpdate'])->name('admin.contact.update');

    //Homepage edit
    Route::get('/homepage-intro', [HomepageController::class, 'homepageIntro'])->name('homepageIntro');
    Route::put('/homepage-intro/update', [HomepageController::class, 'homepageIntroUpdate'])->name('homepageIntro.update');

    //Homepage our values
    Route::get('/homepage-our-values', [HomepageController::class, 'homepageOurValues'])->name('homepageOurValues');
    Route::put('/homepage-our-values/update', [HomepageController::class, 'homepageOurValuesUpdate'])->name('homepageOurValues.update');

    //Servicepage accounting
    Route::get('/servicepage-accounting', [ServicepageController::class, 'servicepageAccounting'])->name('servicepageAccounting');
    Route::put('/servicepage-accounting/update', [ServicepageController::class, 'servicepageAccountingUpdate'])->name('servicepageAccounting.update');

    //Servicepage tax solution
    Route::get('/servicepage-tax-solution', [ServicepageController::class, 'servicepageTaxSolution'])->name('servicepageTaxSolution');
    Route::put('/servicepage-tax-solution/update', [ServicepageController::class, 'servicepageTaxSolutionUpdate'])->name('servicepageTaxSolution.update');

    //Servicepage other solution
    Route::get('/servicepage-other-solution', [ServicepageController::class, 'servicepageOtherSolution'])->name('servicepageOtherSolution');
    Route::put('/servicepage-other-solution/update', [ServicepageController::class, 'servicepageOtherSolutionUpdate'])->name('servicepageOtherSolution.update');

    //Servicepage business startup
    Route::get('/servicepage-business-startup', [ServicepageController::class, 'servicepageBusinessStartup'])->name('servicepageBusinessStartup');
    Route::put('/servicepage-business-startup/update', [ServicepageController::class, 'servicepageBusinessStartupUpdate'])->name('servicepageBusinessStartup.update');

    //Servicepage company Secretarial
    Route::get('/servicepage-company-secretarial', [ServicepageController::class, 'servicepageCompanySecretarial'])->name('servicepageCompanySecretarial');
    Route::put('/servicepage-company-secretarial/update', [ServicepageController::class, 'servicepageCompanySecretarialUpdate'])->name('servicepageCompanySecretarial.update');

    //Servicepage ⁠Bankruptcy and Liquidation
    Route::get('/servicepage-bankruptcy-and-liquidation', [ServicepageController::class, 'bankruptcyAndLiquidation'])->name('bankruptcyAndLiquidation');
    Route::put('/servicepage-bankruptcy-and-liquidation/update', [ServicepageController::class, 'bankruptcyAndLiquidationUpdate'])->name('bankruptcyAndLiquidation.update');

    //Img: we work with crud
    Route::get('/we-work-image', [WeWorkWithImageController::class, 'index'])->name('weWorkImage');
    Route::post('/we-work-image', [WeWorkWithImageController::class, 'store']);
    Route::get('/we-work-image/{id}/edit', [WeWorkWithImageController::class, 'edit']);
    Route::post('/we-work-image-update', [WeWorkWithImageController::class, 'update']);
    Route::get('/we-work-image/{id}', [WeWorkWithImageController::class, 'delete']);

    //Previously logged in staffs
    Route::get('/attendence-records', [StaffController::class, 'prevLogStaffs'])->name('prevLogStaffs');

    Route::get('/all-attendence-records', [StaffController::class, 'allPrevLogStaffs'])->name('allPrevLogStaffs');

    Route::get('/attendance/log/{id}', [StaffController::class, 'attendanceLog'])->name('attendance.log');

    Route::post('/prev-staffs/update/{id}', [StaffController::class, 'updateLogs']);

    // Today's staff's task details and edit by admin
    Route::get('/task-details/{user_id}', [StaffController::class, 'staffTaskDetails'])->name('task.details.staff');

    Route::post('/update-client-service', [StaffController::class, 'updateClientService']);

    // Change Status of sub service
    // Route::post('/update-sub-service-status', [ServiceController::class,'updateSubServiceStatus']);

    // prorota make
    Route::get('/prorota', [ProrotaController::class, 'index'])->name('prorota');
    Route::get('/prorota-list', [ProrotaController::class, 'getprorota'])->name('get.prorota');
    Route::get('/create-prorota', [ProrotaController::class, 'create'])->name('prorota.create');
    Route::post('/prorota', [ProrotaController::class, 'store']);
    Route::post('/prorota/update', [ProrotaController::class, 'update']);
    Route::get('/prorota/details/{id}', [ProrotaController::class,'showDetails'])->name('prorota.details');
    Route::delete('/delete-prorota/{id}', [ProrotaController::class, 'deleteData'])->name('delete.prorota');
    Route::get('/prorota/edit/{id}', [ProrotaController::class,'edit'])->name('prorota.edit');

    Route::get('/prorota-log/{id}', [ProrotaController::class, 'prorotaLog'])->name('prorota.log');

    
    // holiday make
    Route::get('/holiday', [HolidayController::class, 'index'])->name('holiday');
    Route::get('/holiday-list', [HolidayController::class, 'getholiday'])->name('get.holiday');
    Route::get('/create-holiday', [HolidayController::class, 'create'])->name('createholiday');
    Route::post('/holiday', [HolidayController::class, 'store']);
    Route::post('/holiday/update', [HolidayController::class, 'update']);
    Route::get('/holiday/details/{id}', [HolidayController::class,'showDetails'])->name('holiday.details');
    Route::delete('/delete-holiday/{id}', [HolidayController::class, 'deleteData'])->name('delete.holiday');
    Route::get('/holiday/edit/{id}', [HolidayController::class,'edit'])->name('holiday.edit');
    Route::post('/store-holiday', [HolidayController::class, 'storeHoliday'])->name('store.holiday');
    Route::get('/edit-holiday/{id}', [HolidayController::class,'editHoliday'])->name('editHoliday');
    Route::post('/holiday-update/{id}', [HolidayController::class,'updateHoliday']);

    // get holiday type
    Route::post('/get-holiday-type', [HolidayController::class, 'getHolidayType'])->name('get.holiday.type');

    Route::get('/holiday-log/{id}', [HolidayController::class, 'holidayLog'])->name('holiday.log');

    //Holiday Report
    Route::get('/holiday-report', [HolidayController::class, 'holidayReport'])->name('holidayReport');
    Route::post('/get-holiday-data', [HolidayController::class, 'getHolidayData'])->name('getHolidayData');

    //Report
    Route::get('/report', [ReportController::class, 'index'])->name('report.index');

    Route::get('/create-report', [ReportController::class, 'createReport'])->name('report.create');

    Route::get('/generate-report', [ReportController::class, 'generateReport'])->name('report.generate');

    // User absent log
    Route::post('/add-comment', [LogController::class, 'addComment'])->name('add.comment');

    //holiday types
    Route::get('/holiday-type', [HolidayTypeController::class, 'index'])->name('allHolidayType');
    Route::post('/holiday-type', [HolidayTypeController::class, 'store']);
    Route::get('/holiday-type/{id}/edit', [HolidayTypeController::class, 'edit']);
    Route::post('/holiday-type-update', [HolidayTypeController::class, 'update']);
    Route::get('/holiday-type/{id}', [HolidayTypeController::class, 'delete']);

    Route::get('/holiday-type-log/{id}', [HolidayTypeController::class, 'holidayTypeLog'])->name('holidayType.log');

    // company information
    Route::get('/company-details', [CompanyDetailsController::class, 'index'])->name('admin.companyDetail');
    Route::post('/company-details', [CompanyDetailsController::class, 'update'])->name('admin.companyDetails');

    //SoftCode crud
    Route::get('/soft-code', [SoftCodeController::class, 'index'])->name('allSoftCode');    
    Route::post('/soft-code', [SoftCodeController::class, 'store']);
    Route::get('/soft-code/{id}/edit', [SoftCodeController::class, 'edit']);
    Route::post('/soft-code-update', [SoftCodeController::class, 'update']);
    Route::get('/soft-code/{id}', [SoftCodeController::class, 'delete']);

    //Master crud
    Route::get('/master', [MasterController::class, 'index'])->name('allMaster');    
    Route::post('/master', [MasterController::class, 'store']);
    Route::get('/master/{id}/edit', [MasterController::class, 'edit']);
    Route::post('/master-update', [MasterController::class, 'update']);
    Route::get('/master/{id}', [MasterController::class, 'delete']);

    //Timeslot crud
    Route::get('/time-slot', [TimeslotController::class, 'index'])->name('allTimeslot');    
    Route::post('/time-slot', [TimeslotController::class, 'store']);
    Route::get('/time-slot/{id}/edit', [TimeslotController::class, 'edit']);
    Route::post('/time-slot-update', [TimeslotController::class, 'update']);
    Route::get('/time-slot/{id}', [TimeslotController::class, 'delete']);

    //Package Features crud
    Route::get('/package', [PackageController::class, 'index'])->name('allPackage');    
    Route::post('/package', [PackageController::class, 'store']);
    Route::get('/package/{id}/edit', [PackageController::class, 'edit']);
    Route::post('/package-update', [PackageController::class, 'update']);
    Route::get('/package/{id}', [PackageController::class, 'delete']);

    //Package turnover
    route::get('/package-turnover', [PackageController::class, 'showTurnover'])->name('package.turnover');
    Route::post('/package-turnover', [PackageController::class, 'storeTurnover'])->name('package.turnover.store');
    route::get('/package-turnover/{id}/edit', [PackageController::class, 'editTurnover'])->name('package.turnover.edit');
    Route::post('/package-turnover-update', [PackageController::class, 'updateTurnover']);
    Route::get('/package-turnover/{id}', [PackageController::class, 'deleteTurnover']);
    Route::get('/package-turnovers/{id}', [PackageController::class, 'manageTurnovers'])->name('package-turnover');
    
    //Package crud
    Route::get('/package-feature', [PackageFeatureController::class, 'index'])->name('allPackageFeature');    
    Route::post('/package-feature', [PackageFeatureController::class, 'store']);
    Route::get('/package-feature/{id}/edit', [PackageFeatureController::class, 'edit']);
    Route::post('/package-feature-update', [PackageFeatureController::class, 'update']);
    Route::get('/package-feature/{id}', [PackageFeatureController::class, 'delete']);

    //Business service crud
    Route::get('/business-service', [BusinessServiceController::class, 'index'])->name('allBusinessService');    
    Route::post('/business-service', [BusinessServiceController::class, 'store']);
    Route::get('/business-service/{id}/edit', [BusinessServiceController::class, 'edit']);
    Route::post('/business-service-update', [BusinessServiceController::class, 'update']);
    Route::get('/business-service/{id}', [BusinessServiceController::class, 'delete']);

    //Business value crud
    Route::get('/business-value', [BusinessValueController::class, 'index'])->name('allBusinessValue');    
    Route::post('/business-value', [BusinessValueController::class, 'store']);
    Route::get('/business-value/{id}/edit', [BusinessValueController::class, 'edit']);
    Route::post('/business-value-update', [BusinessValueController::class, 'update']);
    Route::get('/business-value/{id}', [BusinessValueController::class, 'delete']);

    //Client testimonial crud
    Route::get('/client-testimonial', [TestimonialController::class, 'index'])->name('allClientTestimonial');    
    Route::post('/client-testimonial', [TestimonialController::class, 'store']);
    Route::get('/client-testimonial/{id}/edit', [TestimonialController::class, 'edit']);
    Route::post('/client-testimonial-update', [TestimonialController::class, 'update']);
    Route::get('/client-testimonial/{id}', [TestimonialController::class, 'delete']);

    //Case Studies crud
    Route::get('/case-studies', [CaseStudiesController::class, 'index'])->name('allCaseStudies');    
    Route::post('/case-studies', [CaseStudiesController::class, 'store']);
    Route::get('/case-studies/{id}/edit', [CaseStudiesController::class, 'edit']);
    Route::post('/case-studies-update', [CaseStudiesController::class, 'update']);
    Route::get('/case-studies/{id}', [CaseStudiesController::class, 'delete']);

    //Latest Insights crud
    Route::get('/latest-insights', [LatestInsightController::class, 'index'])->name('allLatestInsight');    
    Route::post('/latest-insights', [LatestInsightController::class, 'store']);
    Route::get('/latest-insights/{id}/edit', [LatestInsightController::class, 'edit']);
    Route::post('/latest-insights-update', [LatestInsightController::class, 'update']);
    Route::get('/latest-insights/{id}', [LatestInsightController::class, 'delete']);

    //Our Team crud
    Route::get('/our-teams', [OurTeamController::class, 'index'])->name('allOurTeam');    
    Route::post('/our-teams', [OurTeamController::class, 'store']);
    Route::get('/our-teams/{id}/edit', [OurTeamController::class, 'edit']);
    Route::post('/our-teams-update', [OurTeamController::class, 'update']);
    Route::get('/our-teams/{id}', [OurTeamController::class, 'delete']);

    //Booking list
    Route::get('/booking-list', [BookingController::class, 'index'])->name('allBookingList');

    //Quotation list
    Route::get('/quotation-list', [QuotationController::class, 'index'])->name('allQuotationList');
    Route::get('/quotation-list/{id}', [QuotationController::class, 'delete']);

    //Quotation Page
    Route::get('/quotation-page', [QuotationController::class, 'quotationPage'])->name('quotationPage');
    Route::put('/quotation-page', [QuotationController::class, 'quotationPageUpdate'])->name('quotationPage.update');

    //Career Page
    Route::get('/career-page', [CareerController::class, 'careerPage'])->name('careerPage');
    Route::put('/career-page', [CareerController::class, 'careerPageUpdate'])->name('careerPage.update');

    //Career List
    Route::get('/career-list', [CareerController::class, 'index'])->name('allCareerList');
    Route::get('/career-list/{id}', [CareerController::class, 'delete']);

    //Accounting Solution crud
    Route::get('/accounting-solution', [BusinessValueController::class, 'AccoutingSolutionindex'])->name('allAccoutingSolution');    
    Route::post('/accounting-solution', [BusinessValueController::class, 'AccoutingSolutionstore']);
    Route::get('/accounting-solution/{id}/edit', [BusinessValueController::class, 'AccoutingSolutionedit']);
    Route::post('/accounting-solution-update', [BusinessValueController::class, 'AccoutingSolutionupdate']);
    Route::get('/accounting-solution/{id}', [BusinessValueController::class, 'AccoutingSolutiondelete']);

    //Tax Solution crud
    Route::get('/tax-solution', [BusinessValueController::class, 'Taxindex'])->name('allTax');    
    Route::post('/tax-solution', [BusinessValueController::class, 'Taxstore']);
    Route::get('/tax-solution/{id}/edit', [BusinessValueController::class, 'Taxedit']);
    Route::post('/tax-solution-update', [BusinessValueController::class, 'Taxupdate']);
    Route::get('/tax-solution/{id}', [BusinessValueController::class, 'Taxdelete']);

    //Faq Question crud
    Route::get('/faq-questions', [FAQController::class, 'index'])->name('allFaq');    
    Route::post('/faq-questions', [FAQController::class, 'store']);
    Route::get('/faq-questions/{id}/edit', [FAQController::class, 'edit']);
    Route::post('/faq-questions-update', [FAQController::class, 'update']);
    Route::get('/faq-questions/{id}', [FAQController::class, 'delete']);

    //Google Review crud
    Route::get('/google-review', [GoogleReviewController::class, 'index'])->name('allClientReview');    
    Route::post('/google-review', [GoogleReviewController::class, 'store']);
    Route::get('/google-review/{id}/edit', [GoogleReviewController::class, 'edit']);
    Route::post('/google-review-update', [GoogleReviewController::class, 'update']);
    Route::get('/google-review/{id}', [GoogleReviewController::class, 'delete']);

    //Meta Data update
    Route::get('/homepage-meta-data', [MetaDataController::class, 'homeMeta'])->name('homeMeta');
    Route::put('/homepage-meta-data', [MetaDataController::class, 'homeMetaUpdate'])->name('homeMeta.update');

    Route::get('/servicepage-meta-data', [MetaDataController::class, 'serviceMeta'])->name('serviceMeta');
    Route::put('/servicepage-meta-data', [MetaDataController::class, 'serviceMetaUpdate'])->name('serviceMeta.update');

    Route::get('/package-meta-data', [MetaDataController::class, 'packageMeta'])->name('packageMeta');
    Route::put('/package-meta-data', [MetaDataController::class, 'packageMetaUpdate'])->name('packageMeta.update');

    Route::get('/contact-meta-data', [MetaDataController::class, 'contactMeta'])->name('contactMeta');
    Route::put('/contact-meta-data', [MetaDataController::class, 'contactMetaUpdate'])->name('contactMeta.update');

    Route::get('/get-quotation-meta-data', [MetaDataController::class, 'getQuotationMeta'])->name('getQuotationMeta');
    Route::put('/get-quotation-meta-data', [MetaDataController::class, 'getQuotationMetaUpdate'])->name('getQuotationMeta.update');

    Route::get('/career-meta-data', [MetaDataController::class, 'careerMeta'])->name('careerMeta');
    Route::put('/career-meta-data', [MetaDataController::class, 'careerMetaUpdate'])->name('careerMeta.update');

    Route::get('/our-team-meta-data', [MetaDataController::class, 'ourTeamMeta'])->name('ourTeamMeta');
    Route::put('/our-team-meta-data', [MetaDataController::class, 'ourTeamMetaUpdate'])->name('ourTeamMeta.update');

    Route::get('/faq-meta-data', [MetaDataController::class, 'faqMeta'])->name('faqMeta');
    Route::put('/faq-meta-data', [MetaDataController::class, 'faqMetaMetaUpdate'])->name('faqMeta.update');

    Route::get('/privacy-policy-meta-data', [MetaDataController::class, 'privacyMeta'])->name('privacyMeta');
    Route::put('/privacy-policy-meta-data', [MetaDataController::class, 'privacyMetaUpdate'])->name('privacyMeta.update');

    Route::get('/terms-meta-data', [MetaDataController::class, 'termsMeta'])->name('termsMeta');
    Route::put('/terms-meta-data', [MetaDataController::class, 'termsMetaUpdate'])->name('termsMeta.update');

    //Toggle sidebar
    Route::post('/toggle-sidebar', [HomeController::class, 'toggleSidebar'])->name('toggle.sidebar');

    //Contact Mail
    Route::get('/contact-mail', [ContactMailController::class, 'contactMail'])->name('contactMail');
    Route::post('/contact-mail', [ContactMailController::class, 'contactMailUpdate'])->name('contactMail.update');

    // mail content
    Route::get('/mail-content', [MailContentController::class, 'index'])->name('admin.mail-content');
    Route::get('/mail-content/{id}/edit', [MailContentController::class, 'edit']);
    Route::put('/mail-content/{id}', [MailContentController::class, 'update']);

    //One time job
    Route::get('/one-time-job', [OneTimeJobController::class, 'create'])->name('oneTimeJob.create');
    Route::get('/one-time-job/data', [OneTimeJobController::class, 'getData'])->name('client-services.data');
    Route::post('/one-time-job', [OneTimeJobController::class, 'store']);

    Route::get('/one-time-job-activity/{id}', [OneTimeJobController::class, 'showOneTimeJobActivity'])->name('client-service.activity');

    //My tasks
    Route::get('/my-tasks', [AdminController::class, 'getAdminTasks'])->name('my.tasks');
    Route::get('/get-assigned-services', [AdminController::class, 'getAllAssignedServices']);
    // Route::get('/get-note', [AdminController::class, 'getNotes']);
    Route::get('/get-completed-service', [AdminController::class, 'getCompetedServices']);
    Route::get('/get-completed-services-as-manager', [AdminController::class, 'getCompetedServicesAsManager']);
    Route::get('/get-one-time-jobs', [AdminController::class, 'getOneTimeJobs']);
    Route::get('/getClientSubServices-admin/{clientserviceId}', [AdminController::class, 'getClientSubServices']);
    Route::post('client-service-status-chnange', [AdminController::class, 'changeServiceStatus']);
    Route::post('/update-sub-service-staff', [AdminController::class, 'updateSubServiceStaff']);
    Route::post('/update-sub-service-status', [AdminController::class,'updateSubServiceStatus']);
    Route::post('/change-sub-service-status', [AdminController::class,'changeSubServiceStatus']);
    Route::post('/start-work-time', [AdminController::class,'startWorkTime']);
    Route::post('/stop-work-time', [AdminController::class,'stopWorkTime']);
    // Route::get('/getServiceMessage/{clientSubServiceId}', [AdminController::class, 'getServiceMessages']);
    // Route::post('/store-message', [AdminController::class,'storeMessage']);
    Route::post('/update-job-status/{id}', [AdminController::class, 'updateJobStatus']);
    // Route::get('/getServiceComment/{clientServiceId}', [AdminController::class, 'getServiceComment']);
    // Route::post('/store-comment', [AdminController::class, 'storeComment']);

    //Note
    Route::get('/get-note', [NoteController::class, 'getNotes']);
    Route::post('/save-note', [NoteController::class, 'saveNote']);
    Route::post('/assign-note', [NoteController::class, 'assignNote']);

    // roles and permission
    Route::get('/role', [RoleController::class, 'index'])->name('admin.role');
    Route::post('/role', [RoleController::class, 'store'])->name('admin.rolestore');
    Route::get('/role/{id}', [RoleController::class, 'edit'])->name('admin.roleedit');
    Route::post('/role-update', [RoleController::class, 'update'])->name('admin.roleupdate');

    Route::post('/logout', [LogoutController::class, 'logout'])->name('admin.logout');
    
    Route::get('/check-work-time-status', [LogoutController::class,'checkWorkTimeStatus']);

    Route::get('/get-completed-services-modal', [LogoutController::class, 'getCompetedServicesModal']);

    Route::post('/save-notes', [LogoutController::class, 'saveNotes']);

    Route::get('/chats', [ChatController::class, 'getMessages'])->name('chats.get');
    Route::post('/chats/send', [ChatController::class, 'sendMessage'])->name('chats.send');
    Route::get('/unread-messages', [ChatController::class, 'getUnreadMessages'])->name('unread-messages');

    Route::post('/client-about-business', [ClientController::class, 'clientAboutBusinessUpdate'])->name('aboutBusiness.update');

    Route::post('/accountancy-fee', [ClientController::class, 'clientAccountancyFee'])->name('accountancy-fee.storeOrUpdate');

    Route::get('/trash-bin', [TrashBinController::class, 'index'])->name('trash-bin');
    Route::get('/trash-bin/restore', [TrashBinController::class, 'restore'])->name('restore.record');
    Route::get('/trash-bin/delete', [TrashBinController::class, 'forceDelete'])->name('forceDelete.record');

});