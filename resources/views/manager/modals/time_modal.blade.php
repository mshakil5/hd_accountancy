@php
    use Carbon\Carbon;
    use App\Models\UserAttendanceLog;
    use Illuminate\Support\Facades\Auth;

    $startOfDay = Carbon::today()->startOfDay();
    $userId = Auth::user()->id;

    $activeTimeInSeconds = UserAttendanceLog::where('user_id', $userId)
        ->whereNotNull('end_time')
        ->whereBetween('created_at', [$startOfDay, now()])
        ->selectRaw('SUM(duration) as total_duration')
        ->value('total_duration') ?? 0;

    $ongoingSessions = UserAttendanceLog::where('user_id', $userId)
        ->whereNull('end_time')
        ->where('created_at', '>=', $startOfDay)
        ->select('start_time')
        ->get();

    $currentTime = now();
    foreach ($ongoingSessions as $session) {
        $startTime = Carbon::parse($session->start_time);
        $activeTimeInSeconds += $startTime->diffInSeconds($currentTime);
    }

    $activeTimeFormatted = gmdate('H:i:s', $activeTimeInSeconds);
@endphp

<!-- Time Modal -->
<div class="modal fade" id="timeModal2" tabindex="-1" aria-labelledby="timeModalLabel2" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-theme-light border-theme">
                <h5 class="modal-title txt-theme fw-bold" id="timeModal2Label">Your Time</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="report-box border-theme sales-card p-4 rounded-4 border-3">
                    <div class="card-body p-0">
                        <div class="d-flex gap-3 my-5">
                            <div class="text-center flex-fill">
                                <div class="fs-6 txt-theme fw-bold">Active Time</div>
                                <div class="container">
                                    <div class="text-center fs-2 txt-theme fw-bold" id="activeTime">
                                        {{ $activeTimeFormatted ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3 align-items-center justify-content-center">
                            <div class="col-lg-12">
                                <a id="takeBreakBtn" class="p-2 border-theme bg-theme text-center fs-6 d-block rounded-3 border-3 text-light fw-bold" style="cursor: pointer;">Take Break</a>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-lg-12">
                                <a href="#" onclick="checkWorkTimeStatus();" class="p-2 border-theme bg-theme text-center fs-6 d-block rounded-3 border-3 text-light fw-bold">Clock out</a>
                                <form id="logout-form" class="d-none">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Time Modal -->

<!-- Note modal start -->
<div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl mt-2">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <!-- Left Side Section -->
                    <div class="col-lg-4">
                        <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                            <div class="card-body px-0">
                                <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                                    Your tasks
                                </div>

                                <div class="mt-3">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Active Time:</th>
                                                <th>Break Time:</th>
                                                <th>Total Work Time:</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><span id="loginTime"></span></td>
                                                <td><span id="totalBreakTime"></span></td>
                                                <td><span id="totalDuration"></span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div id="completedServices">
                                    <table id="completedServicesTable" class="table">
                                        <thead>
                                            <tr>
                                                <th>Client</th>
                                                <th>Task</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!--  -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side Section -->
                    <div class="col-lg-8">
                        <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100 position-relative">
                            <div class="card-body px-0">
                                <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold">
                                    Add Note
                                </div>
                                <form id="noteForm" method="" action="#">
                                    @csrf
                                    <div class="form-group mt-4">
                                        <label class="fw-bold mr-2">Note:</label>
                                        <textarea class="form-control" id="noteInput" rows="3" name="note" placeholder="Your notes..."></textarea>
                                    </div>

                                    <div class="form-group row mt-3 align-items-center">
                                        <div class="col">
                                            <label class="fw-bold mr-2">Additional Work:</label>
                                        </div>
                                        <div class="col-auto">
                                            <button type="button" class="btn btn-primary" id="addNoteRowBtn">+</button>
                                        </div>
                                    </div>

                                    <div id="additionalWorkRows">
                                        <!-- Rows -->
                                    </div>

                                    <div class="text-right mt-3">
                                        <button type="button" class="btn btn-primary bg-theme-light fs-4 border-theme border-2 fw-bold txt-theme" id="saveNoteBtn">Save Note And Log Out</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Note modal end -->

<div id="breakOutSection" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; backdrop-filter: blur(5px);">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="col-lg-4 mb-3">
            <div class="report-box border-theme sales-card p-4 rounded-4 border-3 h-100">
                <div class="card-body p-0">
                    <div class="p-2 bg-theme-light border-theme border-2 text-center fs-4 txt-theme rounded-4 fw-bold" style="margin-bottom: 35px;">
                        You are on Break
                    </div>
                    
                    <!-- Break Timer -->
                    <div class="text-center mb-4">
                        <div class="fs-6 txt-theme fw-bold">Break Duration</div>
                        <div class="text-center fs-2 txt-theme fw-bold" id="breakTimer">00:00:00</div>
                    </div>

                    <div style="margin-bottom: 10px;"></div>
                    <div class="row mt-10">
                        <div class="col-lg-12">
                            <a id="breakOutBtn" class="p-2 border-theme bg-theme text-center fs-6 d-block rounded-3 border-3 text-light fw-bold" style="cursor: pointer;">End Break</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden field for work time ID -->
<input type="hidden" id="workTimeId" value="">