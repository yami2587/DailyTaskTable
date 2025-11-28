<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Admin;
use Illuminate\Support\Facades\Session;

class AdminAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        // 1) Already logged in?
        if (Session::has('admin_id')) {
            return $next($request);
        }

        // 2) Login via ?userid=xyz (auto-login link)
        if ($request->has('userid')) {

            $admin = Admin::where('userid', $request->userid)->first();

            if ($admin) {

                Session::put('admin_id', $admin->userid);
                Session::put('admin_username', $admin->username);
                Session::put('is_admin', true);

                return redirect()->route('admin.dashboard');
            }

            return abort(401, "Invalid admin userid");
        }

        // 3) No session -> deny access
        return redirect('/admin/login');
    }
}
