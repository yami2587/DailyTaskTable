<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class EmployeeAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        // Already logged in?
        if (Session::has('emp_id')) {
            return $next($request);
        }

        // If URL still has login ?id=xxx, process it first  
        if ($request->has('id')) {

            $emp = DB::table('employee_tbl')
                ->where('emp_id', $request->id)->first();

            if ($emp) {
                Session::put('emp_id', $emp->emp_id);
                Session::put('emp_name', $emp->emp_name);
                return redirect('/dashboard');
            }
        }

        // Otherwise force login
        return redirect('/login');
    }
}
