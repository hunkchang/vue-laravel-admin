<?php

namespace Modules\Admin\Http\Middleware;

use Closure;
//use Models\Admin\Admin\AdminUser;
//use Models\Admin\Admin\WebModules;
//use Models\Admin\Log\DataLog;
use Models\Common\DataLogs;

class OperationLogMiddleware
{
    protected $adminUser;

    public function __construct()
    {
//        /** @var AdminUser $adminUserClass */
//        $adminUserClass  = config ( MODULE . '.adminUserClass' );
//        $this->adminUser = $adminUserClass::getInstance ();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle( $request , Closure $next )
    {
        return $next( $request );
        if ( \Request::method () == 'GET' ) {
            return $next( $request );
        }

        $input       = $request->input ();
        DataLogs::info ($input,$this->adminUser->getUser ());

        if ( empty( $input ) ) {
            return $next( $request );
        }

        return $next( $request );
    }

}
