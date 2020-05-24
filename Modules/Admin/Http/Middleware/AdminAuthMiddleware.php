<?php

namespace Modules\Admin\Http\Middleware;

use App\Facades\Helper\HttpStatus;
use Closure;
use Illuminate\Http\Request;
use Modules\Admin\Entities\Admin\AdminUsers;

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

        $adminUserId = session('admin_user_id');
        if (empty($adminUserId)){
            return  $this->returnError($request);
        }

        $adminUser = AdminUsers::find($adminUserId);
        if (empty($adminUser)){
            return  $this->returnError($request);
        }
        /**
         * 控制单点登录
         */
        $exceptToken = CONTROLLER.'/'.ACTION;

        if (!empty($adminUser->getRememberToken()) && $exceptToken !='Login/Logout'){
            $token = $request->input('token','');
            if (strcmp($token,$adminUser->getRememberToken()) !== 0){
                return $this->returnError($request);
            }
        }

        auth('admin')->setUser($adminUser);
        if (auth('admin')->guest()){
            return  $this->returnError($request);
        }
        return $next($request);
    }

    protected function returnError(Request $request){
        if ($request->ajax() || $request->wantsJson()){
            $status = HttpStatus::HTTP_UNAUTHORIZED;
            return \helper::response([],HttpStatus::$statusTexts[$status],$status);
        }else{
            return redirect('/');
        }
    }
}
