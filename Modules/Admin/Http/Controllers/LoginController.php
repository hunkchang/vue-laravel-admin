<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Admin\Http\Requests\User\LoginRequest;

class LoginController extends Controller
{
    /**
     * 登录
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function postLogin(LoginRequest $request)
    {
        $credentials = $request->only(['username', 'password']);
        if ($this->guard()->attempt($credentials,true)) {
            session()->put('admin_user',$this->guard()->user()->toArray());
            return \helper::response(['token'=>'admin-token'], '登录成功', 20000);
        }

        return \helper::responseError('您填写的账号或者密码不正确');
    }

    public function postLogout(\Request $request)
    {

        $this->guard()->logout();

        return \helper::response('success','success',20000);

    }

    public function getInfo()
    {
        return '{"code":20000,"data":{"roles":["admin"],"introduction":"I am a super administrator","avatar":"https://wpimg.wallstcn.com/f778738c-e4f8-4870-b634-56703b4acafe.gif","name":"Super Admin"}}';
    }

    protected function guard()
    {
        return auth('admin');
    }
}
