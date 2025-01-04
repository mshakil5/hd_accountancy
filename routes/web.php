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
use App\Http\Controllers\Staff\RecentUpdateController;
use App\Http\Controllers\Manager\OneTimeJobController;
use App\Http\Controllers\Manager\NoteController;
  
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
Route::post('/contact', [FrontendController::class, 'contactStore'])->name('frontend.contact.store');

// Pricing
Route::get('/pricing', [FrontendController::class, 'pricing'])->name('frontend.pricing');

// Get Qoutation
Route::get('/get-quotation', [FrontendController::class, 'getQuotation'])->name('frontend.getQuotation');
Route::post('/quotations/store', [FrontendController::class, 'storeQuotation'])->name('quotations.store');

// Services
Route::get('/services', [FrontendController::class, 'services'])->name('frontend.services');

// Our Team
Route::get('/our-team', [FrontendController::class, 'ourTeam'])->name('frontend.ourTeam');

// Career
Route::get('/career', [FrontendController::class, 'career'])->name('frontend.career');

// Booking
Route::get('/booking', [FrontendController::class, 'booking'])->name('frontend.booking');

// Case-Study
Route::get('/case-study', [FrontendController::class, 'caseStudy'])->name('frontend.caseStudy');

// Business Services
route::get('/business-services/{slug}', [FrontendController::class, 'businessServices'])->name('frontend.businessServices');

// Latest Insight Details
route::get('/latest-insights/{slug}', [FrontendController::class, 'latestInsightDetails'])->name('latest-insights.show');

// Service Details
Route::get('/service/{slug}', [FrontendController::class, 'showServiceDetails'])->name('frontend.service.show');

// Video Testimonial
Route::get('/video-testimonials', [FrontendController::class, 'videoTestimonial'])->name('frontend.videoTestimonial');

// FAQ
Route::get('/faq', [FrontendController::class, 'faq'])->name('frontend.faq');

// Latest Insights
Route::get('/latest-insights', [FrontendController::class, 'latestInsights'])->name('frontend.latestInsights');

// Client Testimonials
Route::get('/client-testimonials', [FrontendController::class, 'clientTestimonials'])->name('frontend.clientTestimonials');

// Privacy Policy
Route::get('/privacy-policy', [FrontendController::class, 'privacyPolicy'])->name('frontend.privacyPolicy');

// All Google Reviews
Route::get('/reviews', [FrontendController::class, 'reviews'])->name('frontend.reviews');

// Terms and Conditions
Route::get('/terms-and-conditions', [FrontendController::class, 'termsConditions'])->name('frontend.termsConditions');

//Meeting Schedule Store
Route::post('/schedule-meeting', [FrontendController::class, 'storeSchedule'])->name('schedule.meeting.store');

// Store career
route::post('/career', [FrontendController::class, 'storeCareer'])->name('career.store');

//Base login
Route::get('/dashboard', [HomeController::class, 'index'])->name('home');

//Manager
Route::middleware(['auth', 'user-access:manager'])->group(function () {

    Route::get('/manager/home', [HomeController::class, 'managerHome'])->name('manager.home');

    // Staff profile and profile update
    Route::get('/manager/profile/edit', [ManagerController::class, 'editProfile'])->name('manager.profile.edit');
    Route::post('/manager/profile', [ManagerController::class, 'updateProfile']);

    // Fetch assigned services by manager
    Route::get('/manager/get-assigned-services', [ServiceController::class, 'getAllAssignedServices']);

    //  All task list
    Route::get('/manager/get-all-services', [ServiceController::class, 'getAllServices']);

    // Fetch completed services by manager
    Route::get('/manager/get-completed-services', [ServiceController::class, 'getCompetedServices']);

    Route::get('/manager/get-completed-services-as-manager', [ServiceController::class, 'getCompetedServicesAsManager']);

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

    Route::post('/manager/client-service-change-status', [ServiceController::class, 'changeServiceStatus'])->name('client-service.changeStatus');

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
     Route::post('/manager/save-notes', [ServiceController::class, 'saveNotes'])->name('saveNotes.manager');

     //  Client list
    Route::get('/manager/client', [ClientController::class, 'indexManager'])->name('allClientManager');
    Route::get('/manager/client-list', [ClientController::class, 'getClientsManager'])->name('get.Clients.manager');

    Route::get('/manager/client/{id}/update', [ClientController::class, 'showUpdateByManager'])->name('client.Recent.update.manager');

    //Recent Update
    Route::post('/manager/recent-updates', [RecentUpdateController::class, 'storeByManager'])->name('recent-updates.store.manager');
    Route::post('/manager/recent-updates/{id}', [RecentUpdateController::class, 'updateByManager'])->name('recent-updates.update.manager');
    Route::delete('/manager/recent-updates/{id}', [RecentUpdateController::class, 'destroyByManager'])->name('recent-updates.destroy.manager');

    // Task list
    Route::get('/manager/task-list', [ServiceController::class, 'allTaskList'])->name('allTaskListManager');

    Route::get('/manager/get-one-time-jobs', [ServiceController::class, 'getOneTimeJobs']);
    Route::post('/manager/update-job-status/{id}', [ServiceController::class, 'updateJobStatus']);

    Route::get('/manager/getServiceComment/{clientServiceId}', [ServiceController::class, 'getServiceComment']);

    Route::post('/manager/store-comment', [ServiceController::class, 'storeComment']);

    //One time job
    Route::get('/manager/one-time-job', [OneTimeJobController::class, 'create'])->name('oneTimeJob.create.manager');
    Route::get('/manager/one-time-job/data', [OneTimeJobController::class, 'getData']);
    Route::post('/manager/one-time-job', [OneTimeJobController::class, 'store']);
    
});

//Staff
Route::middleware(['auth', 'user-access:staff'])->group(function () {

    Route::get('/staff/home', [HomeController::class, 'staffHome'])->name('staff.home');

    // Staff profile and profile update
    Route::get('/staff/profile/edit', [StaffController::class, 'editProfile'])->name('staff.profile.edit');
    Route::post('/staff/profile', [StaffController::class, 'updateProfile']);

     //  Task list
     Route::get('/staff/get-assigned-services', [StaffServiceController::class, 'getServicesClientStaff']);

     //  All task list
     Route::get('/staff/get-all-services', [StaffServiceController::class, 'getAllServicesClientStaff']);

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
     Route::post('/staff/save-notes', [StaffServiceController::class, 'saveNotes'])->name('saveNotes.staff');

    //  Client list
    Route::get('/staff/client', [ClientController::class, 'indexStaff'])->name('allClientStaff');
    Route::get('/staff/client-list', [ClientController::class, 'getClientsStaff'])->name('get.Clients.staff');

    Route::get('/staff/client/{id}/update', [ClientController::class, 'showUpdate'])->name('client.Recent.update.staff');

    //Recent Update
    Route::post('/staff/recent-updates', [RecentUpdateController::class, 'store'])->name('recent-updates.store.staff');
    Route::post('/staff/recent-updates/{id}', [RecentUpdateController::class, 'update'])->name('recent-updates.update.staff');
    Route::delete('/staff/recent-updates/{id}', [RecentUpdateController::class, 'destroy'])->name('recent-updates.destroy.staff');

    // Task list
    Route::get('/staff/task-list', [StaffServiceController::class, 'allTaskList'])->name('allTaskList');

    //Idle time count 
    Route::post('/staff-idle-time', [StaffServiceController::class, 'logIdleTime'])->name('staff.idle.time');

    Route::get('/staff/get-one-time-jobs', [StaffServiceController::class, 'getOneTimeJobs']);
    Route::post('/staff/update-job-status/{id}', [StaffServiceController::class, 'updateJobStatus']);

    Route::get('/staff/getServiceComment/{clientServiceId}', [StaffServiceController::class, 'getServiceComment']);

    Route::post('/staff/store-comment', [StaffServiceController::class, 'storeComment']);

    //One time job
    Route::get('/staff/one-time-job', [OneTimeJobController::class, 'createByStaff'])->name('oneTimeJob.create.staff');
    Route::get('/staff/one-time-job/data', [OneTimeJobController::class, 'getDataByStaff'])->name('client-services.data');
    Route::post('/staff/one-time-job', [OneTimeJobController::class, 'storeByStaff']);
    
    Route::get('/staff/get-note', [NoteController::class, 'getNotesByStaff']);
    Route::post('/staff/save-note', [NoteController::class, 'saveNoteByStaff']);
});

//User
Route::middleware(['auth', 'user-access:user'])->group(function () {

    Route::get('user/home', [HomeController::class, 'userHome'])->name('user.home');

});