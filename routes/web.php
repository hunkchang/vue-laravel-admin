<?php

if ( !App::runningInConsole () ) {
    if ( Request::getMethod () == 'OPTIONS' ) {
        //跨域访问的时候才会存在此字段
        $origin       = Request::server ( 'HTTP_ORIGIN' );
        $allow_origin = config ( 'api.allow_domain' );

        if ( in_array ( $origin , $allow_origin ) ) {
            header ( 'Access-Control-Allow-Origin:' . $origin );
            header("Access-Control-Allow-Headers: TOKEN,Origin, X-Requested-With, Content-Type, Accept");
        }

        echo json_encode ( [ 'status' => 200 , 'data' => [] , 'msg' => 'success' ] );
        exit;
    } else {
        App\Dispatch\MyUrlRouteDispatch::instantiate()->run();
    }

}
