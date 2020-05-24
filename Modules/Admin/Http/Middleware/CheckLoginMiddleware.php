<?php

namespace Modules\Admin\Http\Middleware;

use helper;
use Redirect;
use Request;

/**
 * 监测登陆
 * Created by PhpStorm.
 * User: 32823
 * Date: 2016/2/27
 * Time: 22:30
 */
class CheckLoginMiddleware
{
    protected $adminUser;

    public function __construct()
    {
        /** @var AdminUser $adminUserClass */
     /*   $adminUserClass  = config ( MODULE . '.adminUserClass' );
        $this->adminUser = $adminUserClass::getInstance ();*/
    }

    /**
     * @param   Request  $request
     * @param \Closure $next
     * @return \Illuminate\Http\RedirectResponse|mixed|string
     */
    public function handle( $request , \Closure $next )
    {
        $user = $this->adminUser;

        /*if ( !$user->check () ) {
            $fromUrl = base64_encode ( url ()->full () );
            if ( $request->ajax () ) {
                $fromUrl       = base64_encode ( $_SERVER[ 'HTTP_REFERER' ] );
                $var[ 'link' ] = \helper::url ( '/login/index/?from=' . $fromUrl );
                $view          = \View::make ( 'admin::login.timeout' , $var )->render ();
                echo $view;
                exit;
            }
            return Redirect::to ( helper::url ( '/login/index/?from=' . $fromUrl ) );
        }

        if ( !$user->hasAccess () && !in_array ( CONTROLLER , [ 'notfound' , 'NotFound' ] ) ) {
            if ( Request::ajax () ) {
                return \helper::returnAjax ( 401 , trans ( 'admin::common.operation.Fobidden' ) , \helper::url ( '/not_found' ) );
            }

            return Redirect::to ( \helper::url ( '/not_found' ) );
        }*/
        return $next( $request );
    }

}

