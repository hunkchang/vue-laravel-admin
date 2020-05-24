<?php

namespace Modules\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Admin\Entities\Admin\AdminUsers;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle(Request $request, Closure $next)
    {

        $adminUserId = session('admin_user.admin_user_id');
        if (empty($adminUserId)){
            return  $this->returnError($request);
        }

        $adminUser = AdminUsers::find($adminUserId);
        auth('admin')->setUser($adminUser);
        if (auth('admin')->guest()){
            return  $this->returnError($request);
        }
        return $next($request);
    }

    protected function returnError(Request $request){
        if ($request->ajax() || $request->wantsJson()){
            $status = Response::HTTP_UNAUTHORIZED;
            return \helper::response([],Response::$statusTexts[$status],$status);
        }else{
            return redirect('/');
        }
    }
}
