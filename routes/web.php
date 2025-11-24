<?php

use App\Http\Controllers\DailySheetController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// login page (GET /login) - view provided earlier
// Route::get('/login', function () {
//     return view('auth.login');
// })->name('login');

Route::get('/login', function (Request $r) {

    // 1) If URL has ?id=xxx
    if ($r->has('id')) {

        $emp = DB::table('employee_tbl')->where('emp_id', $r->id)->first();

        if ($emp) {
            // store login session
            session([
                'emp_id' => $emp->emp_id,
                'emp_name' => $emp->emp_name
            ]);

            return redirect('/dashboard');
        }

        return redirect()->back()->with('success', 'Invalid Employee ID');
    }

    // 2) Else show login screen
    return view('auth.login', [
        'msg' => session('success')
    ]);
})->name('login');

// protect dashboard and sheet routes with employee.auth middleware
Route::middleware(['employee.auth'])->group(function () {

    Route::get('/dashboard', [DailySheetController::class, 'dashboard'])->name('tasktable');

    // Leader actions:
    Route::post('/sheet/create', [DailySheetController::class, 'createSheet'])->name('sheet.create');
    Route::post('/assign', [DailySheetController::class, 'assign'])->name('assign');
    Route::post('/assignment/{assign}/update', [DailySheetController::class, 'updateAssignment'])->name('assign.update');
    Route::post('/assignment/{assign}/delete', [DailySheetController::class, 'deleteAssignment'])->name('assign.delete');

    // Member submit status
    Route::post('/assignment/{assign}/submit', [DailySheetController::class, 'memberSubmit'])->name('assign.submit');

    // Save final day log (leader)
    Route::post('/sheet/{sheet}/save-day', [DailySheetController::class, 'saveDayLog'])->name('sheet.save_day');
});






use App\Http\Controllers\TeamController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TodayTargetController;
use App\Http\Controllers\TeamDailyLogController;
use App\Http\Controllers\EmployeeDashboardController;


//////
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\DailySheetController;



// Route::get('/login', function(Request $r){
//     // If query has id, the middleware also handles it, but this page helps users enter id
//     $msg = session('success') ?: null;
//     return view('auth.login', ['msg'=>$msg]);
// })->name('login');

// Route::middleware(['employee.auth'])->group(function(){
//     Route::get('/dashboard', [DailySheetController::class,'dashboard'])->name('tasktable'); // main page
//     // other protected routes...
// });









// Route::get('tasktable', [DailySheetController::class,'dashboard'])->name('tasktable');

// // Leader actions
// Route::post('tasktable/sheet/create', [DailySheetController::class,'createSheet'])->name('sheet.create');
// Route::post('tasktable/assign', [DailySheetController::class,'assign'])->name('assign');
// Route::post('tasktable/assignment/{assign}/update', [DailySheetController::class,'updateAssignment'])->name('assign.update');
// Route::post('tasktable/assignment/{assign}/delete', [DailySheetController::class,'deleteAssignment'])->name('assign.delete');

// // Member actions
// Route::post('tasktable/assignment/{assign}/submit', [DailySheetController::class,'memberSubmit'])->name('assign.submit');
// Route::post('tasktable/assignment/{assign}/complete', [DailySheetController::class,'memberComplete'])->name('assign.complete');

// // Targets
// Route::post('tasktable/sheet/{sheet}/targets', [DailySheetController::class,'updateTargets'])->name('targets.update');




// ////////////////////////




// Route::get('/', function () {
//     return redirect()->route('daily-logs.index');
// });

////////////
// main parts


// Route::get('tasktable/{emp_id?}', [EmployeeDashboardController::class,'show'])->name('tasktable.show');

// Route::post('tasktable/sheet/create', [EmployeeDashboardController::class,'createSheet'])->name('tasktable.sheet.create');

// Route::post('tasktable/assignment/{assignment}/assign', [EmployeeDashboardController::class,'assignUpdate'])->name('tasktable.assignment.assign');

// Route::post('tasktable/assignment/{assignment}/member-update', [EmployeeDashboardController::class,'memberUpdate'])->name('tasktable.assignment.member_update');

// Route::post('tasktable/assignment/{assignment}/complete', [EmployeeDashboardController::class,'markComplete'])->name('tasktable.assignment.complete');

// Route::post('tasktable/sheet/{sheet}/update-header', [EmployeeDashboardController::class,'updateSheetHeader'])->name('tasktable.sheet.update_header');




// Teams (with member management)
Route::resource('team', TeamController::class);

// custom member routes
Route::get('team/{team}/members', [TeamController::class, 'members'])->name('team.members');
Route::post('team/{team}/members', [TeamController::class, 'addMember'])->name('team.members.add');
Route::delete('team/{team}/members/{member}', [TeamController::class, 'removeMember'])->name('team.members.remove');

// Tasks
// Route::resource('tasks', TaskController::class);

// Today Targets
Route::resource('targets', TodayTargetController::class);
Route::post('targets/{target}/toggle', [TodayTargetController::class, 'toggleDone'])->name('targets.toggle');

// Daily logs
Route::get('daily-logs', [TeamDailyLogController::class, 'index'])->name('daily-logs.index');
Route::get('daily-logs/create', [TeamDailyLogController::class, 'create'])->name('daily-logs.create');
Route::post('daily-logs', [TeamDailyLogController::class, 'store'])->name('daily-logs.store');
Route::delete('daily-logs/{teamDailyLog}', [TeamDailyLogController::class, 'destroy'])->name('daily-logs.destroy');

// Manual quick trigger (admin)
Route::get('daily-logs/generate/{date?}', function ($date = null) {
    return app(\App\Http\Controllers\TeamDailyLogController::class)->autoGenerateForDate($date);
})->name('daily-logs.generate');
