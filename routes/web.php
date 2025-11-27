<?php

use App\Http\Controllers\DailySheetController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AdminController;

Route::get('/login', function (Request $r) {
    if ($r->has('id')) {
        $emp = DB::table('employee_tbl')->where('emp_id', $r->id)->first();
        if ($emp) {
            session([
                'emp_id' => $emp->emp_id,
                'emp_name' => $emp->emp_name,
                'employee_name' => $emp->emp_name,
            ]);
            if ($emp->emp_id == '1') {
                session(['is_admin' => true]);
            }

            return redirect('/dashboard');
        }
        return redirect()->back()->with('success', 'Invalid Employee ID');
    }
    return view('auth.login', ['msg' => session('success')]);
})->name('login');

Route::middleware(['employee.auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DailySheetController::class, 'dashboard'])->name('tasktable');

    // Leader actions
    Route::post('/sheet/create', [DailySheetController::class, 'createSheet'])->name('sheet.create');           // normal form
    Route::post('/assign', [DailySheetController::class, 'assign'])->name('assign');                             // AJAX + form
    Route::post('/assign/{assign}', [DailySheetController::class, 'updateAssignment'])->name('assign.update');   // update (form)
    Route::post('/assign/delete/{assign}', [DailySheetController::class, 'deleteAssignment'])->name('assign.delete'); // delete

    // Member submit (AJAX capable)
    Route::post('/assign/submit/{assign}', [DailySheetController::class, 'memberSubmit'])->name('assign.submit');

    // Save today's target (AJAX) & finalize day (AJAX-capable)
    Route::post('/sheet/save_day/{sheet}', [DailySheetController::class, 'saveDayLog'])->name('sheet.save_day');

    // Unfreeze helper (leader only — testing)
    Route::post('/sheet/unfreeze/{sheet}', [DailySheetController::class, 'unfreezeSheet'])->name('sheet.unfreeze');


    // Leader personal member-style dashboard
    Route::get('/dashboard/mine', [DailySheetController::class, 'myDashboard'])->name('dashboard.mine');
});

// Admin routes (super admin)
Route::middleware(['employee.auth'])->group(function () {
    // Admin dashboard (list teams, select date)
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');

    // convenience redirect - opens admin for team
    Route::get('/admin/team/{team}', [AdminController::class, 'team'])->name('admin.team');
});





//////
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TodayTargetController;
use App\Http\Controllers\TeamDailyLogController;
use App\Http\Controllers\EmployeeDashboardController;

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
