<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\DailySheetController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TeamController;


/*
| Employee Login
*/

Route::get('/login', function (Request $r) {
    
    
    
    if ($r->has('id')) {
        $id = $r->id; // direct login - local/dev
        // $token = $r->id;
        // $token = base64_decode($token);
        // $array = explode('-',$token);
        // $timestamp = $array[0];
        // $id = $array[1];
       
        // $current_time = time();  
        // $five_minutes = 5 * 60;
        
        // if (($current_time - $timestamp) > $five_minutes) {
        //     echo "Token Expired";die;
        // }

        $emp = \App\Models\Employee::where('emp_id', $id)->first();

        if ($emp) {
            session([
                'emp_id'   => $emp->emp_id,
                'emp_name' => $emp->emp_name,
            ]);

            // promote emp_id=1 to admin (legacy compatibility)
            if ($emp->emp_id == 1) {
                session(['is_admin' => true]);
            }

            return redirect()->route('tasktable');
        }

        return back()->with('success', 'Invalid Employee ID');
    }

    return view('auth.login', ['msg' => session('success')]);
})->name('login');


/*
| Employee Protected Routes
*/
Route::middleware(['employee.auth'])->group(function () {

    Route::get('/dashboard', [DailySheetController::class, 'dashboard'])
        ->name('tasktable');

    Route::post('/sheet/create', [DailySheetController::class, 'createSheet'])
        ->name('sheet.create');

    Route::post('/assign', [DailySheetController::class, 'assign'])
        ->name('assign');

    Route::post('/assign/{assign}', [DailySheetController::class, 'updateAssignment'])
        ->name('assign.update');

    Route::post('/assign/delete/{assign}', [DailySheetController::class, 'deleteAssignment'])
        ->name('assign.delete');

    Route::post('/assign/submit/{assign}', [DailySheetController::class, 'memberSubmit'])
        ->name('assign.submit');

    Route::post('/sheet/save_day/{sheet}', [DailySheetController::class, 'saveDayLog'])
        ->name('sheet.save_day');

    Route::post('/sheet/unfreeze/{sheet}', [DailySheetController::class, 'unfreezeSheet'])
        ->name('sheet.unfreeze');

    Route::get('/dashboard/mine', [DailySheetController::class, 'myDashboard'])
        ->name('dashboard.mine');
});


/*
| Admin Login + Admin Protected
*/

Route::get('/admin/login', function (Request $r) {
    if ($r->has('userid')) {
        // $username = base64_decode($r->userid);
        $username = ($r->userid);//direct login - local/dev
        $admin = \App\Models\Admin::where('username', $username)->first();

        if ($admin) {
            session([
                'admin_id'       => $admin->userid,
                'admin_username' => $admin->username,
                'is_admin'       => true,
            ]);

            return redirect()->route('admin.dashboard');
        }
        return back()->with('error', 'Invalid admin userid');
    }

    return view('auth.admin-login', ['msg' => session('error')]);
})->name('admin.login');


Route::middleware(['admin.auth'])->group(function () {

    Route::get('/admin', [AdminController::class, 'index'])
        ->name('admin.dashboard');

    Route::get('/admin/team/{team}', [AdminController::class, 'team'])
        ->name('admin.team');


    // Teams (with member management)
    Route::resource('team', TeamController::class);

    // custom member routes
    Route::get('team/{team}/members', [TeamController::class, 'members'])->name('team.members');
    Route::post('team/{team}/members', [TeamController::class, 'addMember'])->name('team.members.add');
    Route::delete('team/{team}/members/{member}', [TeamController::class, 'removeMember'])->name('team.members.remove');
});


